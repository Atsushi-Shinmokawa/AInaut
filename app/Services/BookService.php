<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\BookData;
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
     * 書籍登録（ISBN検索 + 保存 + ReadingLog作成）
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

        if ($query === '') {
            return [
                'books' => $books,
                'filters' => ['q' => ''],
                'hasSearched' => false,
            ];
        }

        // ISBNかどうかを判定（10桁または13桁の数字のみ）
        // 注意: FormRequestでISBNの場合は既に正規化済み、キーワードの場合はそのまま
        $isIsbn = strlen($query) === 10 || strlen($query) === 13;

        if ($isIsbn) {
            // ISBN検索（既にFormRequestで正規化済み）
            $bookData = $this->searchByIsbn($query);
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

        $chatThreads = $allThreads->map(fn (BookThread $t) => [
            'id' => $t->id,
            'title' => $t->title ?? ('会話 ' . $t->created_at->format('n/j H:i')),
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
            ] : null,
            'chatMessages' => $chatMessages,
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
        // ISBNが空または10桁・13桁でない場合は検索しない
        // 注意: FormRequestで既に正規化・検証済みだが、念のためチェック
        if ($isbn === '' || (strlen($isbn) !== 10 && strlen($isbn) !== 13)) {
            Log::warning("Invalid ISBN format: {$isbn}");
            return null;
        }

        try {
            // Level 3: タイムアウト設定とリトライ処理を入れる
            // 429エラー（レート制限）の場合はリトライしない
            $response = Http::timeout(5)
                ->retry(3, 100, function ($exception, $request) {
                    // 429エラー（レート制限）の場合はリトライしない
                    if ($exception instanceof RequestException && $exception->response?->status() === 429) {
                        return false; // リトライしない
                    }
                    // その他のエラー（タイムアウト、ネットワークエラーなど）はリトライ
                    return true;
                })
                ->get(self::GOOGLE_BOOKS_API_URL, [
                    'q' => "isbn:{$isbn}",
                ]);

            if ($response->failed()) {
                // レート制限エラー（429）の場合は特別な例外を投げる
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

            // ISBN10で検索して結果がなかった場合、ISBN13に変換して再検索
            if (($data['totalItems'] ?? 0) === 0 && strlen($isbn) === 10) {
                $isbn13 = $this->convertIsbn10To13($isbn);
                if ($isbn13) {
                    Log::info("ISBN10 search failed, retrying with ISBN13: {$isbn13}");
                    $response = Http::timeout(5)
                        ->retry(3, 100, function ($exception, $request) {
                            // 429エラー（レート制限）の場合はリトライしない
                            if ($exception instanceof RequestException && $exception->response?->status() === 429) {
                                return false; // リトライしない
                            }
                            // その他のエラー（タイムアウト、ネットワークエラーなど）はリトライ
                            return true;
                        })
                        ->get(self::GOOGLE_BOOKS_API_URL, [
                            'q' => "isbn:{$isbn13}",
                        ]);

                    if ($response->failed()) {
                        // レート制限エラー（429）の場合は特別な例外を投げる
                        if ($response->status() === 429) {
                            throw new AppServiceExternalApiException(
                                'Google Books APIの利用制限に達しました。しばらく時間をおいてから再度お試しください。',
                                $response->status(),
                                'Google Books API',
                                self::GOOGLE_BOOKS_API_URL,
                                $response->body()
                            );
                        }
                    }

                    if ($response->successful()) {
                        $data = $response->json();
                        if (($data['totalItems'] ?? 0) > 0) {
                            $isbn = $isbn13; // 検索に成功したISBN13を使用
                        }
                    }
                }
            }

            if (($data['totalItems'] ?? 0) === 0) {
                Log::info("No results found for ISBN: {$isbn}");
                return null;
            }

            // 最初の1件を取得
            $item = $data['items'][0];
            $volumeInfo = $item['volumeInfo'];

            // ISBNをレスポンスから取得（ISBN13を優先、なければISBN10、それもなければ検索に使ったISBN）
            $foundIsbn = $isbn;
            if (isset($volumeInfo['industryIdentifiers'])) {
                foreach ($volumeInfo['industryIdentifiers'] as $identifier) {
                    if ($identifier['type'] === 'ISBN_13') {
                        $foundIsbn = $identifier['identifier'];
                        break;
                    } elseif ($identifier['type'] === 'ISBN_10' && $foundIsbn === $isbn) {
                        $foundIsbn = $identifier['identifier'];
                    }
                }
            }

            // 文字列のUTF-8エンコーディングを確認・修正
            $title = $volumeInfo['title'] ?? '不明なタイトル';
            $title = mb_convert_encoding($title, 'UTF-8', 'UTF-8');

            $authors = array_map(function ($author) {
                return mb_convert_encoding($author, 'UTF-8', 'UTF-8');
            }, $volumeInfo['authors'] ?? ['不明な著者']);

            $description = isset($volumeInfo['description'])
                ? mb_convert_encoding($volumeInfo['description'], 'UTF-8', 'UTF-8')
                : null;

            // DTOに変換して返す（データの正規化）
            return new BookData(
                title: $title,
                subTitle: isset($volumeInfo['subtitle'])
                    ? mb_convert_encoding($volumeInfo['subtitle'], 'UTF-8', 'UTF-8')
                    : null,
                authors: $authors,
                isbn: $foundIsbn,
                publisher: isset($volumeInfo['publisher'])
                    ? mb_convert_encoding($volumeInfo['publisher'], 'UTF-8', 'UTF-8')
                    : null,
                publishedAt: isset($volumeInfo['publishedDate'])
                    ? Carbon::parse($volumeInfo['publishedDate'])
                    : null,
                description: $description,
                coverUrl: $volumeInfo['imageLinks']['thumbnail'] ?? null,
                rawResponse: $item // 後でmeta_dataテーブルに入れるために生データも保持
            );

        } catch (AppServiceExternalApiException $e) {
            // 外部API例外（429エラーなど）は再スローしてGlobal Exception Handlerで処理
            throw $e;
        } catch (RequestException $e) {
            // ネットワークエラー等の例外処理
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
    }

    /**
     * キーワード検索（タイトル・著者名など）
     *
     * @param string $keyword 検索キーワード
     * @return BookData[] 検索結果の配列（最大10件）
     */
    private function searchByKeyword(string $keyword): array
    {
        try {
            // UTF-8エンコーディングを確認・修正
            $keyword = mb_convert_encoding($keyword, 'UTF-8', 'UTF-8');

            // タイムアウト設定とリトライ処理
            // 429エラー（レート制限）の場合はリトライしない
            $response = Http::timeout(5)
                ->retry(3, 100, function ($exception, $request) {
                    // 429エラー（レート制限）の場合はリトライしない
                    if ($exception instanceof RequestException && $exception->response?->status() === 429) {
                        return false; // リトライしない
                    }
                    // その他のエラー（タイムアウト、ネットワークエラーなど）はリトライ
                    return true;
                })
                ->get(self::GOOGLE_BOOKS_API_URL, [
                    'q' => $keyword,
                    'maxResults' => 10,
                ]);

            if ($response->failed()) {
                // レート制限エラー（429）の場合は特別な例外を投げる
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

                // ISBNを取得（ISBN13を優先、なければISBN10）
                $isbn = null;
                if (isset($volumeInfo['industryIdentifiers'])) {
                    foreach ($volumeInfo['industryIdentifiers'] as $identifier) {
                        if ($identifier['type'] === 'ISBN_13') {
                            $isbn = $identifier['identifier'];
                            break;
                        } elseif ($identifier['type'] === 'ISBN_10' && $isbn === null) {
                            $isbn = $identifier['identifier'];
                        }
                    }
                }

                // 文字列のUTF-8エンコーディングを確認・修正
                $title = $volumeInfo['title'] ?? '不明なタイトル';
                $title = mb_convert_encoding($title, 'UTF-8', 'UTF-8');

                $authors = array_map(function ($author) {
                    return mb_convert_encoding($author, 'UTF-8', 'UTF-8');
                }, $volumeInfo['authors'] ?? ['不明な著者']);

                $description = isset($volumeInfo['description'])
                    ? mb_convert_encoding($volumeInfo['description'], 'UTF-8', 'UTF-8')
                    : null;

                $results[] = new BookData(
                    title: $title,
                    subTitle: isset($volumeInfo['subtitle'])
                        ? mb_convert_encoding($volumeInfo['subtitle'], 'UTF-8', 'UTF-8')
                        : null,
                    authors: $authors,
                    isbn: $isbn,
                    publisher: isset($volumeInfo['publisher'])
                        ? mb_convert_encoding($volumeInfo['publisher'], 'UTF-8', 'UTF-8')
                        : null,
                    publishedAt: isset($volumeInfo['publishedDate'])
                        ? Carbon::parse($volumeInfo['publishedDate'])
                        : null,
                    description: $description,
                    coverUrl: $volumeInfo['imageLinks']['thumbnail'] ?? null,
                    rawResponse: $item
                );
            }

            return $results;

        } catch (AppServiceExternalApiException $e) {
            // 外部API例外（429エラーなど）は再スローしてGlobal Exception Handlerで処理
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

        // ISBN10の最初の9桁を取得（最後の1桁はチェックディジット）
        $prefix = '978'; // 書籍のISBN13のプレフィックス
        $isbn13Base = $prefix . substr($isbn10, 0, 9);

        // チェックディジットを計算
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $digit = (int)$isbn13Base[$i];
            $sum += ($i % 2 === 0) ? $digit : $digit * 3;
        }

        $checkDigit = (10 - ($sum % 10)) % 10;
        return $isbn13Base . $checkDigit;
    }
}
