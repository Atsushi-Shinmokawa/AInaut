<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookChatSendRequest;
use App\Models\Book;
use App\Services\Chat\BookChatService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class BookChatController extends Controller
{
    public function __construct(
        private readonly BookChatService $bookChatService,
    ) {}

    public function send(BookChatSendRequest $request, Book $book): RedirectResponse
    {
        $userId = (string) Auth::id();

        // 例外はGlobal Exception Handlerで自動的に処理される
        $this->bookChatService->send($book, $userId, $request->validated('content'));
        
        return back()->with('success', '送信しました');
    }
}
