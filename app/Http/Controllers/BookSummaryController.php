<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Services\Ai\BookSummaryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookSummaryController extends Controller
{
    public function __construct(
        private readonly BookSummaryService $service
    ) {}

    public function generate(Request $request, Book $book): RedirectResponse
    {
        $userId = (string) Auth::id();

        // ここで権限チェックを入れたいなら入れる（例：BookPolicy）
        // $this->authorize('view', $book);

        $this->service->generateAndStore(
            book: $book,
            userId: $userId,
        );

        return redirect()
            ->route('books.show', ['book' => $book->id, 'tab' => 'summary'])
            ->with('success', 'AI要約を生成しました');
    }
}
