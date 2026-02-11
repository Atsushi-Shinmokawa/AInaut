<?php

namespace App\Services\Chat;

use App\Enums\BookMessageRole;
use App\Models\Book;
use App\Models\BookMessage;
use App\Models\BookThread;
use Illuminate\Support\Facades\DB;

class BookChatService
{
    public function __construct(
        private BookContextBuilder $contextBuilder,
        private OpenAiChatClient $client,
    ) {}

    /**
     * 本に紐づくスレッドへユーザー発言を追加し、AI応答を保存して返す
     */
    public function send(Book $book, string $userId, string $content): string
    {
        // 1) user message を保存（短いDB処理なのでここはtransactionでOK）
        $thread = null;

        DB::transaction(function () use ($book, $userId, $content, &$thread) {
    $thread = BookThread::firstOrCreate(
        ['user_id' => $userId, 'book_id' => $book->id],
        ['title' => null],
    );

    if (!mb_check_encoding($content, 'UTF-8')) {
        throw new \InvalidArgumentException('Invalid UTF-8 content');
    }

    BookMessage::create([
        'book_thread_id' => $thread->id,
        'user_id'        => $userId,
        'book_id'        => $book->id,
        'role'           => BookMessageRole::USER,
        'content'        => $content,
        'char_length'    => mb_strlen($content, 'UTF-8'),
    ]);
});

        assert($thread instanceof BookThread);

        // 2) 文脈を組み立て（DB読み取り + 文字列整形）
        $recent = BookMessage::where('book_thread_id', $thread->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->reverse()
            ->values();

        $bookContext = $this->contextBuilder->build($book, maxChars: 9000);

        $messages = [];

        $messages[] = [
            'role' => BookMessageRole::SYSTEM->value,
            'content' =>
"あなたは読書支援AIです。回答は日本語で、根拠がある場合は「どの情報（highlights/chunks）に基づくか」を短く添えてください。
不確かな場合は推測と断り、必要なら質問して確認してください。",
        ];

        $messages[] = [
            'role' => BookMessageRole::SYSTEM->value,
            'content' => "以下は本の関連情報です。回答に活用してください。\n\n" . $bookContext,
        ];

        foreach ($recent as $m) {
            $messages[] = [
                'role' => $m->role->value,
                'content' => $m->content,
            ];
        }

        // 3) OpenAI呼び出し（外部I/O。DBロック中にやらない）
        $answer = $this->client->chat($messages);

        // 4) assistant message を保存（短いDB処理）
        DB::transaction(function () use ($thread, $book, $userId, $answer) {
            BookMessage::create([
                'book_thread_id' => $thread->id,
                'user_id'        => $userId,
                'book_id'        => $book->id,
                'role'           => BookMessageRole::ASSISTANT,
                'content'        => $answer,
                'char_length'    => mb_strlen($answer),
            ]);
        });

        return $answer;
    }

    /**
     * 既に保存済みのユーザーメッセージに対してAI応答を生成し、assistantメッセージを保存する。
     * 非同期ジョブから呼び出す想定（ユーザーメッセージはControllerで保存済み）。
     */
    public function generateResponseForUserMessage(BookMessage $userMessage): void
    {
        $thread = $userMessage->thread;
        $book = $userMessage->book;

        if (!$thread || !$book) {
            return;
        }

        $recent = BookMessage::where('book_thread_id', $thread->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->reverse()
            ->values();

        $bookContext = $this->contextBuilder->build($book, maxChars: 9000);

        $messages = [];
        $messages[] = [
            'role' => BookMessageRole::SYSTEM->value,
            'content' =>
                "あなたは読書支援AIです。回答は日本語で、根拠がある場合は「どの情報（highlights/chunks）に基づくか」を短く添えてください。\n不確かな場合は推測と断り、必要なら質問して確認してください。",
        ];
        $messages[] = [
            'role' => BookMessageRole::SYSTEM->value,
            'content' => "以下は本の関連情報です。回答に活用してください。\n\n" . $bookContext,
        ];
        foreach ($recent as $m) {
            $messages[] = [
                'role' => $m->role->value,
                'content' => $m->content,
            ];
        }

        $answer = $this->client->chat($messages);

        DB::transaction(function () use ($thread, $book, $userMessage, $answer) {
            BookMessage::create([
                'book_thread_id' => $thread->id,
                'user_id'        => $userMessage->user_id,
                'book_id'        => $book->id,
                'role'           => BookMessageRole::ASSISTANT,
                'content'        => $answer,
                'char_length'    => mb_strlen($answer),
            ]);
        });
    }
}
