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

    public function store(StoreBookRequest $request, AddBookToShelfService $shelfService): RedirectResponse
{
    $rawIsbn = $request->validated('isbn');
    $isbn = preg_replace('/[^0-9Xx]/', '', $rawIsbn);

    $bookData = $this->searchService->searchByIsbn($isbn);

    if (!$bookData) {
        return back()->with('error', '本が見つかりませんでした。');
    }

    $shelfService->add((string) Auth::id(), $bookData);

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

    return inertia('Books/Index', [
        'items' => ReadingLogResource::collection($logs),
    ]);
}
public function show(Book $book, BookShowQueryService $service)
{
    $userId = (string) Auth::id();

    $props = $service->buildProps($book, $userId);

    return Inertia::render('Books/Show', $props);
}

}