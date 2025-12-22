<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookRequest;
use App\Models\Book;
use App\Models\BookHighlight;
use App\Services\BookSearchService;
use App\Services\BookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\ReadingLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Response;
use Inertia\Inertia;
use App\Models\BookDocument;
use App\Models\BookChunk;

class BookController extends Controller
{
    public function __construct(
        private readonly BookSearchService $searchService,
        private readonly BookService $bookService
    ) {}

    /**
     * ISBNを受け取り、本を検索して保存する
     */
    public function store(StoreBookRequest $request): RedirectResponse
    {
        // 1. バリデーション済みのデータを取得
        $rawIsbn = $request->validated('isbn');

        $isbn    = preg_replace('/[^0-9Xx]/', '', $rawIsbn);

        // 2. Google Books APIで検索 (なければ404エラーを返す例)
        $bookData = $this->searchService->searchByIsbn($isbn);

        if (!$bookData) {
            // JSONではなく、元の画面へ戻ってエラーメッセージを渡す
            return back()->with('error', '本が見つかりませんでした。');

        }

        // 3. データベースに保存 (Authorも自動処理)
        $book = $this->bookService->persist($bookData);

        // ⭐ v1でも「マイ本棚」に紐づくようにしておく
    ReadingLog::firstOrCreate(
        [
            'user_id' => Auth::id(),
            'book_id' => $book->id,
        ],
        [
            'status' => 'want_to_read',
        ],
    );

    return back()->with('success', '本棚に追加しました。');
    }

    /**
     * 書籍検索画面を表示する（または検索処理を行う）
     *
     * @param Request $request
     * @return \Inertia\Response
     */
    public function search(Request $request)
{
    $q = $request->input('q', '');

    // ISBN っぽい場合は、検索用にハイフン等を除去
    $normalizedIsbn = preg_replace('/[^0-9Xx]/', '', $q);

    $books = [];

    if ($q !== '') {
        // まずは v1 では「ISBN検索」にだけ対応しておく
        // 後で「書名キーワード検索」を BookSearchService 側に増やせばよい
        $bookData = $this->searchService->searchByIsbn($q);

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
    }

    return inertia('Books/Search', [
        'books'   => $books,
        'filters' => [
            'q' => $q,
        ],
        'hasSearched' => $request->has('q') && $q !== '',
    ]);
}

public function index(Request $request): Response
{
    $logs = ReadingLog::with(['book.author'])
        ->where('user_id', Auth::id())
        ->latest()
        ->get();

    // Inertia に渡すために、最低限の形に整形
    $items = $logs->map(fn ($log) => [
        'id'        => $log->id,
        'status'    => $log->status,
        'created_at'=> $log->created_at?->toDateString(),
        'book' => [
            'id'        => $log->book->id,
            'title'     => $log->book->title,
            'author'    => $log->book->author?->name,
            'cover_url' => $log->book->cover_url,
        ],
    ]);

    return inertia('Books/Index', [
        'items' => $items,
    ]);
}
public function show(Book $book)
{
    // この本に紐づいているハイライト
    $highlights = BookHighlight::where('book_id', $book->id)
        ->orderByDesc('created_at')
        ->get();

    // 未紐付け救済候補（title_raw が近いもの）
    $orphanHighlights = BookHighlight::whereNull('book_id')
        ->whereNotNull('title_raw')
        ->where('title_raw', 'like', '%' . $book->title . '%')
        ->limit(20)
        ->get();


        $userId = Auth::id();

        $document = BookDocument::where('book_id', $book->id)
            ->where('user_id', $userId)
            ->first();
    
        $chunksPreview = [];
        if ($document) {
            $chunksPreview = BookChunk::where('book_document_id', $document->id)
                ->orderBy('chunk_index')
                ->limit(5)
                ->get();
        }

    return Inertia::render('Books/Show', [
        'book' => $book,
        'highlights' => $highlights,
        'orphanHighlights' => $orphanHighlights,
        'document' => $document,
        'chunksPreview' => $chunksPreview,
    ]);
}

}