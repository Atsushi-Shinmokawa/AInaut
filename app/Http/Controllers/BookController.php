<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookRequest;
use App\Models\AiSummary;
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
use App\Models\BookThread;
use App\Models\BookMessage;

class BookController extends Controller
{
    public function __construct(
        private readonly BookSearchService $searchService,
        private readonly BookService $bookService
    ) {}

    /**
     * ISBNを受け取り、本を検索して保存する
     */
    // BookController
public function store(StoreBookRequest $request): RedirectResponse
{
    $result = $this->bookService->store(
        $request->validated('isbn'),
        (string) Auth::id()
    );

    return back()->with(
        $result['success'] ? 'success' : 'error',
        $result['message']
    );
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
public function show(Book $book, BookShowQueryService $service)
{
    $userId = (string) Auth::id();

    $props = $service->buildProps($book, $userId);

    return Inertia::render('Books/Show', $props);
}

}
