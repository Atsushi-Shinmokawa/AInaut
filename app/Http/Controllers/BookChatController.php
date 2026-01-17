<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Services\Chat\BookChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Client\RequestException;

class BookChatController extends Controller
{
    public function __construct(
        private readonly BookChatService $bookChatService,
    ) {}

    public function send(Request $request, Book $book)
    {
        $data = $request->validate([
            'content' => ['required', 'string', 'max:4000'],
        ]);

        $userId = (string) Auth::id();

        try {
            $this->bookChatService->send($book, $userId, $data['content']);
            return back()->with('success', '送信しました');
        } catch (RequestException $e) {
            report($e);

            $status = $e->response?->status();
            if ($status === 429) {
                return back()->with('error', 'AIが混雑しています。少し時間をおいて再度お試しください。');
            }
            if ($status === 401 || $status === 403) {
                return back()->with('error', 'AI機能の設定に問題があります。管理者に連絡してください。');
            }

            return back()->with('error', 'AIとの通信に失敗しました。時間をおいて再度お試しください。');
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', '予期しないエラーが発生しました。時間をおいて再度お試しください。');
        }
    }
}
