<?php

namespace App\Http\Controllers;

use App\Exceptions\AppServiceExternalApiException;
use App\Http\Requests\BookSearchRequest;
use App\Http\Requests\StoreBookRequest;
use App\Models\Book;
use App\Services\BookService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Response;
use Inertia\Inertia;


class BookController extends Controller
{
    public function __construct(
        private readonly BookService $bookService
    ) {}

    /**
     * ISBNを受け取り、本を検索して保存する
     */
public function store(StoreBookRequest $request): RedirectResponse
{
    $data = $request->validatedData();
    $result = $this->bookService->store(
        $data['isbn'],
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
     * @return Response
     */
    public function search(BookSearchRequest $request): Response
    {
        try {
            $data = $request->validatedData();
            $query = $data['q'] ?? '';
            $props = $this->bookService->search($query);

            return Inertia::render('Books/Search', $props);
        } catch (AppServiceExternalApiException $e) {
            // 外部API例外（429エラーなど）は、検索画面にエラーメッセージを表示
            $data = $request->validatedData();
            $query = $data['q'] ?? '';
            $props = [
                'books' => [],
                'filters' => ['q' => $query],
                'hasSearched' => true,
                'error' => $e->getUserMessage(),
            ];

            return Inertia::render('Books/Search', $props);
        }
    }

    public function show(Book $book): Response
    {
        $userId = (string) Auth::id();
        $props = $this->bookService->buildShowProps($book, $userId);
        return Inertia::render('Books/Show', $props);
    }

}
