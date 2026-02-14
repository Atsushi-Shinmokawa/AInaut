<?php

namespace App\Http\Controllers;

use App\Enums\ChatCharacter;
use App\Enums\BookMessageRole;
use App\Http\Requests\BookChatSendRequest;
use App\Jobs\ProcessChatMessageJob;
use App\Models\Book;
use App\Models\BookMessage;
use App\Models\BookThread;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class BookChatController extends Controller
{
    public function send(BookChatSendRequest $request, Book $book): RedirectResponse
    {
        Log::info('BookChatController::send start', ['book_id' => $book->id]);

        $userId = (string) Auth::id();
        $content = $request->validated('content');
        $threadId = $request->validated('thread_id');

        if (!mb_check_encoding($content, 'UTF-8')) {
            return back()->withErrors(['content' => '不正な文字が含まれています。']);
        }

        $thread = null;
        if ($threadId) {
            $thread = BookThread::where('book_id', $book->id)
                ->where('user_id', $userId)
                ->find($threadId);
        }
        if (!$thread) {
            $thread = BookThread::create([
                'user_id' => $userId,
                'book_id' => $book->id,
                'title'   => null,
            ]);
        }

        $userMessage = BookMessage::create([
            'book_thread_id' => $thread->id,
            'user_id'        => $userId,
            'book_id'        => $book->id,
            'role'           => BookMessageRole::USER,
            'content'        => $content,
            'char_length'    => mb_strlen($content, 'UTF-8'),
        ]);

        ProcessChatMessageJob::dispatch($userMessage->id);

        Log::info('BookChatController::send job dispatched', ['user_message_id' => $userMessage->id]);

        return redirect()
            ->route('books.show', ['book' => $book->id, 'tab' => 'chat', 'thread' => $thread->id])
            ->with('success', 'メッセージを送信しました。AIが応答を生成しています…');
    }

    /**
     * ポーリング用: スレッドのメッセージ数と最終メッセージの role を返す。
     * lastMessageRole が 'assistant' かつ messageCount が増えていればAI応答完了。
     * thread クエリでスレッドIDを指定可能。未指定時は最新スレッド。
     */
    public function status(Book $book): JsonResponse
    {
        $userId = (string) Auth::id();
        $threadId = request()->query('thread');

        $thread = null;
        if ($threadId) {
            $thread = BookThread::where('book_id', $book->id)
                ->where('user_id', $userId)
                ->find($threadId);
        }
        if (!$thread) {
            $thread = BookThread::where('book_id', $book->id)
                ->where('user_id', $userId)
                ->orderByDesc('updated_at')
                ->first();
        }

        if (!$thread) {
            return response()->json([
                'messageCount'    => 0,
                'lastMessageRole' => null,
            ]);
        }

        $lastMessage = BookMessage::where('book_thread_id', $thread->id)
            ->orderByDesc('created_at')
            ->first();

        return response()->json([
            'messageCount'    => (int) BookMessage::where('book_thread_id', $thread->id)->count(),
            'lastMessageRole' => $lastMessage ? $lastMessage->role->value : null,
        ]);
    }

    /**
     * スレッドのキャラを切り替える（認可: 自分のスレッドのみ）
     */
    public function updateThreadCharacter(Request $request, Book $book, string $thread): RedirectResponse
    {
        $userId = (string) Auth::id();

        $threadModel = BookThread::where('id', $thread)
            ->where('book_id', $book->id)
            ->where('user_id', $userId)
            ->firstOrFail();

        $validated = $request->validate([
            'character' => ['required', 'string', Rule::in([
                ChatCharacter::ZUNDAMON->value,
                ChatCharacter::METAN->value,
                ChatCharacter::TSUMUGI->value,
            ])],
        ]);

        $threadModel->update(['character' => $validated['character']]);

        return redirect()
            ->route('books.show', ['book' => $book->id, 'tab' => 'chat', 'thread' => $threadModel->id])
            ->with('success', 'キャラを切り替えました。');
    }
}
