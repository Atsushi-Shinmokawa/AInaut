<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Services\Ai\BookSummaryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class BookSummaryController extends Controller
{

public function __construct(
        private readonly BookSummaryService $bookSummaryService,
    ) {}

    public function generate(Book $book): RedirectResponse
    {
        $userId = (string) Auth::id();

        // 例外はGlobal Exception Handlerで自動的に処理される
        $this->bookSummaryService->generateAndSave($book, $userId);

        return back()->with('success', 'AI要約を生成しました。');
    }
}
