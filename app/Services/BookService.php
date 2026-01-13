<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\BookData;
use App\Models\Author;
use App\Models\Book;
use App\Models\ReadingLog;
use App\Models\BookHighlight;
use App\Models\BookDocument;
use App\Models\BookChunk;
use App\Models\BookThread;
use App\Models\BookMessage;
use App\Models\AiSummary;
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
     */
    public function store(string $rawIsbn, string $userId): array
    {
        // ISBN正規化
        $isbn = preg_replace('/[^0-9Xx]/', '', $rawIsbn);

        // 書籍検索
        $bookData = $this->searchByIsbn($isbn);
        if (!$bookData) {
            return ['success' => false, 'message' => '本が見つかりませんでした。'];
        }

        // 書籍保存
        $book = $this->persist($bookData);

        // ReadingLog作成
        ReadingLog::firstOrCreate(
            ['user_id' => $userId, 'book_id' => $book->id],
            ['status' => 'want_to_read'],
        );

        return ['success' => true, 'message' => '本棚に追加しました。'];
    }

    /**
     * 書籍検索（ISBN検索に対応、将来的にキーワード検索にも対応可能）
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

        // ISBN正規化（ハイフン等を除去）
        $normalizedIsbn = preg_replace('/[^0-9Xx]/', '', $query);

        // 現在はISBN検索のみ対応
        $bookData = $this->searchByIsbn($normalizedIsbn);

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
     * @return array Inertia用のprops形式
     */
    public function buildShowProps(Book $book, string $userId): array
    {
        // 1. この本に紐づくハイライト（book_idが設定されているもの）
        $highlights = BookHighlight::where('book_id', $book->id)
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($h) => [
                'id' => $h->id,
                'content' => $h->content,
                'page' => $h->page,
                'location' => $h->location,
                'created_at' => $h->created_at?->toIso8601String(),
                'title_raw' => $h->title_raw,
            ])
            ->toArray();

        // 2. 未紐付けのハイライト（book_idがnullのもの）
        // 取り込み時の書名が近いものを候補として表示
        $orphanHighlights = BookHighlight::whereNull('book_id')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($h) => [
                'id' => $h->id,
                'content' => $h->content,
                'page' => $h->page,
                'location' => $h->location,
                'created_at' => $h->created_at?->toIso8601String(),
                'title_raw' => $h->title_raw,
            ])
            ->toArray();

        // 3. 本文ドキュメント
        $document = BookDocument::where('book_id', $book->id)
            ->where('user_id', $userId)
            ->first();

        // 4. チャンクプレビュー（最初の3つ）
        $chunksPreview = [];
        if ($document) {
            $chunksPreview = $document->chunks()
                ->orderBy('chunk_index')
                ->limit(3)
                ->get()
                ->map(fn ($c) => [
                    'id' => $c->id,
                    'content' => $c->content,
                    'chunk_index' => $c->chunk_index,
                ])
                ->toArray();
        }

        // 5. チャットスレッドとメッセージ
        $chatThread = BookThread::where('book_id', $book->id)
            ->where('user_id', $userId)
            ->first();

        $chatMessages = [];
        if ($chatThread) {
            $chatMessages = BookMessage::where('book_thread_id', $chatThread->id)
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(fn ($m) => [
                    'id' => $m->id,
                    'role' => $m->role,
                    'content' => $m->content,
                    'created_at' => $m->created_at?->toIso8601String(),
                ])
                ->toArray();
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
            'chatThread' => $chatThread ? [
                'id' => $chatThread->id,
            ] : null,
            'chatMessages' => $chatMessages,
            'latestSummary' => $latestSummary ? [
                'id' => $latestSummary->id,
                'content' => $latestSummary->content,
                'created_at' => $latestSummary->created_at?->toIso8601String(),
            ] : null,
        ];
    }

    /**
     * ISBNで書籍を検索し、BookData DTOを返す
     *
     * @param string $isbn
     * @return BookData|null 見つからない場合はnull
     */
    private function searchByIsbn(string $isbn): ?BookData
    {
        // 念のためここでも normalize しておいても良い
        $isbn = preg_replace('/[^0-9Xx]/', '', $isbn);

        try {
            // Level 3: タイムアウト設定とリトライ処理を入れる
            $response = Http::timeout(5)
                ->retry(3, 100) // 100ms間隔で3回リトライ
                ->get(self::GOOGLE_BOOKS_API_URL, [
                    'q' => "isbn:{$isbn}",
                ]);

            if ($response->failed()) {
                Log::warning("Google Books API Error: " . $response->status());
                return null;
            }

            $data = $response->json();

            if (($data['totalItems'] ?? 0) === 0) {
                return null;
            }

            // 最初の1件を取得
            $item = $data['items'][0];
            $volumeInfo = $item['volumeInfo'];

            // DTOに変換して返す（データの正規化）
            return new BookData(
                title: $volumeInfo['title'] ?? '不明なタイトル',
                subTitle: $volumeInfo['subtitle'] ?? null,
                authors: $volumeInfo['authors'] ?? ['不明な著者'],
                isbn: $isbn,
                publisher: $volumeInfo['publisher'] ?? null,
                publishedAt: isset($volumeInfo['publishedDate'])
                    ? Carbon::parse($volumeInfo['publishedDate'])
                    : null,
                description: $volumeInfo['description'] ?? null,
                coverUrl: $volumeInfo['imageLinks']['thumbnail'] ?? null,
                rawResponse: $item // 後でmeta_dataテーブルに入れるために生データも保持
            );

        } catch (RequestException $e) {
            // ネットワークエラー等の例外処理
            Log::error("Book Search Exception: " . $e->getMessage());
            return null;
        } catch (\Exception $e) {
            Log::error("Book Search Unexpected Error: " . $e->getMessage());
            return null;
        }
    }
}
