<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\BookData;
use App\Enums\ChatCharacter;
use App\Enums\ReadingLogStatus;
use App\Exceptions\AppServiceExternalApiException;
use App\Models\Author;
use App\Models\Book;
use App\Models\ReadingLog;
use App\Models\BookHighlight;
use App\Models\BookDocument;
use App\Models\BookChunk;
use App\Models\BookThread;
use App\Models\BookMessage;
use App\Models\AiSummary;
use App\Resources\AiSummaryResource;
use App\Resources\BookChunkResource;
use App\Resources\BookHighlightResource;
use App\Resources\BookMessageResource;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class BookService
{

// Google Books API エンドポイント
    private const GOOGLE_BOOKS_API_URL = 'https://www.googleapis.com/books/v1/volumes';


    /**
     * 外部APIで取得した BookData をDBに永続化して Book を返す
     * - authorは “最初の著者名” を採用（v1）
     * - 同一ISBNが既にあれば update して返す（v1の安全策）
     */
    public function persist(BookData $data): Book
    {
        $authorName = $data->authors[0] ?? 'Unknown';

        $author = Author::firstOrCreate(
            ['name' => $authorName],
            ['memo' => null],
        );

        // ISBNがあれば同一判定をしやすいので活用（なければ新規）
        $book = null;
        if (!empty($data->isbn)) {
            $book = Book::where('isbn', $data->isbn)->first();
        }

        $payload = [
            'author_id' => $author->id,
            'title' => $data->title,
            'subtitle' => $data->subTitle,
            'isbn' => $data->isbn,
            'publisher' => $data->publisher,
            'published_at' => $data->publishedAt,
            'description' => $data->description,
            'cover_url' => $data->coverUrl,
            'raw_api_response' => $data->rawResponse,
        ];

        if ($book) {
            $book->fill($payload)->save();
            return $book;
        }

        return Book::create($payload);
    }

/**
     * 書籍登録（ISBN検索   保存   ReadingLog作成）
     *
     * @param string $isbn 正規化済みのISBN（FormRequestで正規化済み）
     * @param string $userId
     * @return array
     */
    public function store(string $isbn, string $userId): array
    {
        // 書籍検索（ISBNは既にFormRequestで正規化済み）
        $bookData = $this->searchByIsbn($isbn);
        if (!$bookData) {
            return ['success' => false, 'message' => '本が見つかりませんでした。'];
        }

        // 書籍保存
        $book = $this->persist($bookData);

        // ReadingLog作成
        ReadingLog::firstOrCreate(
            ['user_id' => $userId, 'book_id' => $book->id],
            ['status' => ReadingLogStatus::WANT_TO_READ],
        );

        return ['success' => true, 'message' => '本棚に追加しました。'];
    }

    /**
     * 書籍検索（ISBN検索とキーワード検索に対応）
     * Inertia用に整形済みのデータを返す
     *
     * @param string $query 検索クエリ（ISBNまたはキーワード）
     * @return array Inertia用のprops形式
     */
    public function search(string $query): array
    {
        $books = [];
        $query = trim($query);

        if ($query === '') {
            return [
                'books' => [],
                'filters' => ['q' => ''],
                'hasSearched' => false,
            ];
        }

        // ISBN判定を厳密化
         $isIsbn = $this->isLikelyIsbn($query);

        if ($isIsbn) {
            $normalized = strtoupper(str_replace(['-', ' '], '', $query));
             $bookData = $this->searchByIsbn($normalized);
            if ($bookData) {
                $books = [
                    [
                        'title'     => $bookData->title,
                        'authors'   => $bookData->authors,
                        'isbn'      => $bookData->isbn,
                        'thumbnail' => $bookData->coverUrl,
                    ],
                ];
            }
        } else {
            // キーワード検索
            $searchResults = $this->searchByKeyword($query);
            $books = array_map(function ($bookData) {
                return [
                    'title'     => $bookData->title,
                    'authors'   => $bookData->authors,
                    'isbn'      => $bookData->isbn,
                    'thumbnail' => $bookData->coverUrl,
                ];
            }, $searchResults);
        }

        return [
            'books' => $books,
            'filters' => ['q' => $query],
            'hasSearched' => true,
        ];
    }

    /**
      * ISBNらしさ判定（Service側でも二重防御）
      */
    private function isLikelyIsbn(string $query): bool
    {
        $normalized = strtoupper(str_replace(['-', ' '], '', $query));

         if (preg_match('/^\d{13}$/', $normalized) === 1) {
             return true;
         }

         if (preg_match('/^\d{9}[\dX]$/', $normalized) === 1) {
             return true;
         }

         return false;
     }

     /**
      * Google Books API共通パラメータ
      */
     private function googleBooksParams(array $params = []): array
     {
         $base = [
             'key' => config('services.google_books.key'),
             'printType' => 'books',
             'langRestrict' => 'ja',
         ];

         return array_filter(array_merge($base, $params), fn ($v) => $v !== null && $v !== '');
     }

     /**
     * 書籍詳細画面に必要なデータを取得して返す
     *
     * @param Book $book
     * @param string $userId
     * @param string|null $threadId 表示するスレッドID。未指定時は最新スレッド
     * @return array Inertia用のprops形式
     */
    public function buildShowProps(Book $book, string $userId, ?string $threadId = null): array
    {
        // 1. この本に紐づくハイライト（book_idが設定されているもの）
        $highlights = BookHighlightResource::collection(
            BookHighlight::where('book_id', $book->id)
                ->where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->get()
        )->resolve();

        // 2. 未紐付けのハイライト（book_idがnullのもの）
        // 取り込み時の書名が近いものを候補として表示
        $orphanHighlights = BookHighlightResource::collection(
            BookHighlight::whereNull('book_id')
                ->where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->get()
        )->resolve();

        // 3. 本文ドキュメント
        $document = BookDocument::where('book_id', $book->id)
            ->where('user_id', $userId)
            ->first();

        // 4. チャンクプレビュー（最初の3つ）
        $chunksPreview = [];
        if ($document) {
            $chunksPreview = BookChunkResource::collection(
                $document->chunks()
                    ->orderBy('chunk_index')
                    ->limit(3)
                    ->get()
            )->resolve();
        }

        // 5. チャットスレッド一覧と表示スレッド・メッセージ（本ごとに複数スレッド可）
        $allThreads = BookThread::where('book_id', $book->id)
            ->where('user_id', $userId)
            ->orderByDesc('updated_at')
            ->get();

        $chatModelOptions = array_map(fn (array $row) => [
            'id' => $row['id'],
            'label' => $row['label'],
            'model' => $row['model'],
        ], config('services.openai.chat_models', []));

        $chatThreads = $allThreads->map(fn (BookThread $t) => [
            'id' => $t->id,
            'title' => $t->title ?? ('会話 ' . $t->created_at->format('n/j H:i')),
            'character' => $t->character ?? 'zundamon',
            'model' => $t->model,
            'updated_at' => $t->updated_at->toIso8601String(),
        ])->values()->all();

        $chatThread = null;
        if ($threadId) {
            $chatThread = $allThreads->firstWhere('id', $threadId);
        }
        // thread が URL に無いときは「新規チャット」状態のため、既存スレッドにフォールバックしない

        $chatMessages = [];
        if ($chatThread) {
            $chatMessages = BookMessageResource::collection(
                BookMessage::where('book_thread_id', $chatThread->id)
                    ->orderBy('created_at', 'asc')
                    ->get()
            )->resolve();
        }

        // 6. 最新の要約
        $latestSummary = AiSummary::where('book_id', $book->id)
            ->where('user_id', $userId)
            ->latest()
            ->first();

        return [
            'book' => [
                'id' => $book->id,
                'title' => $book->title,
            ],
            'highlights' => $highlights,
            'orphanHighlights' => $orphanHighlights,
            'document' => $document ? [
                'id' => $document->id,
                'source_type' => $document->source,
            ] : null,
            'chunksPreview' => $chunksPreview,
            'chatThreads' => $chatThreads,
            'chatThread' => $chatThread ? [
                'id' => $chatThread->id,
                'character' => $chatThread->character ?? 'zundamon',
                'model' => $chatThread->model,
            ] : null,
            'chatMessages' => $chatMessages,
            'characterOptions' => ChatCharacter::optionsForFrontend(),
            'chatModelOptions' => $chatModelOptions,
            'ttsConfig' => [
                'qualityOptions' => config('tts.quality_options', []),
                'defaultModel' => config('tts.openai.model', 'tts-1'),
                'defaultSpeed' => (float) config('tts.openai.speed', 1.0),
                'backendOptions' => config('tts.backend_options', []),
                'voicevoxLocalBaseUrl' => config('tts.voicevox_local_url', 'http://127.0.0.1:50021'),
                'characterSpeakerIds' => config('tts.character_speaker_ids', []),
            ],
            'latestSummary' => $latestSummary ? AiSummaryResource::make($latestSummary)->resolve() : null,
        ];
    }

    /**
     * ISBNで書籍を検索し、BookData DTOを返す
     *
     * @param string $isbn 正規化済みのISBN（FormRequestで正規化済み）
     * @return BookData|null 見つからない場合はnull
     */
    private function searchByIsbn(string $isbn): ?BookData
{
    if ($isbn === '' || (strlen($isbn) !== 10 && strlen($isbn) !== 13)) {
        Log::warning("Invalid ISBN format: {$isbn}");
        return null;
    }

    $cacheKey = 'google_books:isbn:' . $isbn;

    return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($isbn) {
        try {
            $response = Http::timeout(5)
                ->retry(3, 100, function ($exception) {
                    if ($exception instanceof RequestException && $exception->response?->status() === 429) {
                        return false;
                    }
                    return true;
                })
                ->get(self::GOOGLE_BOOKS_API_URL, $this->googleBooksParams([
                    'q' => "isbn:{$isbn}",
                ]));

            if ($response->failed()) {
                if ($response->status() === 429) {
                    throw new AppServiceExternalApiException(
                        'Google Books APIの利用制限に達しました。しばらく時間をおいてから再度お試しください。',
                        $response->status(),
                        'Google Books API',
                        self::GOOGLE_BOOKS_API_URL,
                        $response->body()
                    );
                }

                Log::warning("Google Books API Error: " . $response->status());
                return null;
            }

            $data = $response->json();

            if (($data['totalItems'] ?? 0) === 0 && strlen($isbn) === 10) {
                $isbn13 = $this->convertIsbn10To13($isbn);
                if ($isbn13) {
                    Log::info("ISBN10 search failed, retrying with ISBN13: {$isbn13}");

                    $response = Http::timeout(5)
                        ->retry(3, 100, function ($exception) {
                            if ($exception instanceof RequestException && $exception->response?->status() === 429) {
                                return false;
                            }
                            return true;
                        })
                        ->get(self::GOOGLE_BOOKS_API_URL, $this->googleBooksParams([
                            'q' => "isbn:{$isbn13}",
                        ]));

                    if ($response->failed() && $response->status() === 429) {
                        throw new AppServiceExternalApiException(
                            'Google Books APIの利用制限に達しました。しばらく時間をおいてから再度お試しください。',
                            $response->status(),
                            'Google Books API',
                            self::GOOGLE_BOOKS_API_URL,
                            $response->body()
                        );
                    }

                    if ($response->successful()) {
                        $data = $response->json();
                        if (($data['totalItems'] ?? 0) > 0) {
                            $isbn = $isbn13;
                        }
                    }
                }
            }

            if (($data['totalItems'] ?? 0) === 0) {
                Log::info("No results found for ISBN: {$isbn}");
                return null;
            }

            $item = $data['items'][0];
            $volumeInfo = $item['volumeInfo'] ?? [];

            $foundIsbn = $isbn;
            foreach (($volumeInfo['industryIdentifiers'] ?? []) as $identifier) {
                if (($identifier['type'] ?? null) === 'ISBN_13') {
                    $foundIsbn = $identifier['identifier'];
                    break;
                } elseif (($identifier['type'] ?? null) === 'ISBN_10' && $foundIsbn === $isbn) {
                    $foundIsbn = $identifier['identifier'];
                }
            }

            $title = mb_convert_encoding($volumeInfo['title'] ?? '不明なタイトル', 'UTF-8', 'UTF-8');

            $authors = array_map(
                fn ($author) => mb_convert_encoding($author, 'UTF-8', 'UTF-8'),
                $volumeInfo['authors'] ?? ['不明な著者']
            );

            $description = isset($volumeInfo['description'])
                ? mb_convert_encoding($volumeInfo['description'], 'UTF-8', 'UTF-8')
                : null;

            return new BookData(
                title: $title,
                subTitle: isset($volumeInfo['subtitle']) ? mb_convert_encoding($volumeInfo['subtitle'], 'UTF-8', 'UTF-8') : null,
                authors: $authors,
                isbn: $foundIsbn,
                publisher: isset($volumeInfo['publisher']) ? mb_convert_encoding($volumeInfo['publisher'], 'UTF-8', 'UTF-8') : null,
                publishedAt: isset($volumeInfo['publishedDate']) ? Carbon::parse($volumeInfo['publishedDate']) : null,
                description: $description,
                coverUrl: $volumeInfo['imageLinks']['thumbnail'] ?? null,
                rawResponse: $item
            );
        } catch (AppServiceExternalApiException $e) {
            throw $e;
        } catch (RequestException $e) {
            Log::error("Book Search Exception: " . $e->getMessage(), [
                'isbn' => $isbn,
                'exception' => get_class($e),
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error("Book Search Unexpected Error: " . $e->getMessage(), [
                'isbn' => $isbn,
                'exception' => get_class($e),
            ]);
            return null;
        }
    });
}


    /**
     * キーワード検索（タイトル・著者名など）
     *
     * @param string $keyword 検索キーワード
     * @return BookData[] 検索結果の配列（最大10件）
     */
    private function searchByKeyword(string $keyword): array
{
    $keyword = trim(mb_convert_encoding($keyword, 'UTF-8', 'UTF-8'));
    if ($keyword === '') {
        return [];
    }

    $cacheKey = 'google_books:kw:' . md5(mb_strtolower($keyword));

    return Cache::remember($cacheKey, now()->addMinutes(3), function () use ($keyword) {
        try {
            $response = Http::timeout(5)
                ->retry(3, 100, function ($exception) {
                    if ($exception instanceof RequestException && $exception->response?->status() === 429) {
                        return false;
                    }
                    return true;
                })
                ->get(self::GOOGLE_BOOKS_API_URL, $this->googleBooksParams([
                    'q' => $keyword,
                    'maxResults' => 10,
                ]));

            if ($response->failed()) {
                if ($response->status() === 429) {
                    throw new AppServiceExternalApiException(
                        'Google Books APIの利用制限に達しました。しばらく時間をおいてから再度お試しください。',
                        $response->status(),
                        'Google Books API',
                        self::GOOGLE_BOOKS_API_URL,
                        $response->body()
                    );
                }

                Log::warning("Google Books API Error: " . $response->status());
                return [];
            }

            $data = $response->json();
            if (($data['totalItems'] ?? 0) === 0) {
                return [];
            }

            $results = [];
            foreach ($data['items'] ?? [] as $item) {
                $volumeInfo = $item['volumeInfo'] ?? [];

                $isbn = null;
                foreach (($volumeInfo['industryIdentifiers'] ?? []) as $identifier) {
                    if (($identifier['type'] ?? null) === 'ISBN_13') {
                        $isbn = $identifier['identifier'];
                        break;
                    } elseif (($identifier['type'] ?? null) === 'ISBN_10' && $isbn === null) {
                        $isbn = $identifier['identifier'];
                    }
                }

                $title = mb_convert_encoding($volumeInfo['title'] ?? '不明なタイトル', 'UTF-8', 'UTF-8');

                $authors = array_map(
                    fn ($author) => mb_convert_encoding($author, 'UTF-8', 'UTF-8'),
                    $volumeInfo['authors'] ?? ['不明な著者']
                );

                $description = isset($volumeInfo['description'])
                    ? mb_convert_encoding($volumeInfo['description'], 'UTF-8', 'UTF-8')
                    : null;

                $results[] = new BookData(
                    title: $title,
                    subTitle: isset($volumeInfo['subtitle']) ? mb_convert_encoding($volumeInfo['subtitle'], 'UTF-8', 'UTF-8') : null,
                    authors: $authors,
                    isbn: $isbn,
                    publisher: isset($volumeInfo['publisher']) ? mb_convert_encoding($volumeInfo['publisher'], 'UTF-8', 'UTF-8') : null,
                    publishedAt: isset($volumeInfo['publishedDate']) ? Carbon::parse($volumeInfo['publishedDate']) : null,
                    description: $description,
                    coverUrl: $volumeInfo['imageLinks']['thumbnail'] ?? null,
                    rawResponse: $item
                );
            }

            return $results;
        } catch (AppServiceExternalApiException $e) {
            throw $e;
        } catch (RequestException $e) {
            Log::error("Book Keyword Search Exception: " . $e->getMessage(), [
                'keyword' => $keyword,
                'exception' => get_class($e),
            ]);
            return [];
        } catch (\Exception $e) {
            Log::error("Book Keyword Search Unexpected Error: " . $e->getMessage(), [
                'keyword' => $keyword,
                'exception' => get_class($e),
            ]);
            return [];
        }
    });
}


    /**
     * ISBN10をISBN13に変換
     *
     * @param string $isbn10 ISBN10（10桁）
     * @return string|null ISBN13（13桁）、変換できない場合はnull
     */
    private function convertIsbn10To13(string $isbn10): ?string
{
    if (strlen($isbn10) !== 10) {
        return null;
    }

    $prefix = '978';
    $isbn13Base = $prefix . substr($isbn10, 0, 9);

    $sum = 0;
    for ($i = 0; $i < 12; $i++) {
        $digit = (int) $isbn13Base[$i];
        $sum += ($i % 2 === 0) ? $digit : $digit * 3;
    }

    $checkDigit = (10 - ($sum % 10)) % 10;
    return $isbn13Base . $checkDigit;
}

}
