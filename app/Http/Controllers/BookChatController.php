<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookThread;
use App\Models\BookMessage;
use App\Services\Chat\BookContextBuilder;
use App\Services\Chat\OpenAiChatClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BookChatController extends Controller
{
  public function send(Request $request, Book $book, BookContextBuilder $contextBuilder, OpenAiChatClient $client)
  {
    $data = $request->validate([
      'content' => ['required', 'string', 'max:4000'],
    ]);

    $userId = Auth::id();

    try {
      DB::transaction(function () use ($book, $userId, $data, $contextBuilder, $client) {
        // スレッド（本ごとに1つ）
        $thread = BookThread::firstOrCreate(
          ['user_id' => $userId, 'book_id' => $book->id],
          ['title' => null],
        );

        // user message保存
        BookMessage::create([
          'book_thread_id' => $thread->id,
          'user_id' => $userId,
          'book_id' => $book->id,
          'role' => 'user',
          'content' => $data['content'],
          'char_length' => mb_strlen($data['content']),
        ]);

        // 会話履歴（直近N件を文脈に入れる）
        $recent = BookMessage::where('book_thread_id', $thread->id)
          ->orderByDesc('created_at')
          ->limit(10)
          ->get()
          ->reverse()
          ->values();

        // 本コンテキスト（highlights + notes + chunks）
        $bookContext = $contextBuilder->build($book, maxChars: 9000);

        // OpenAIに渡す messages（system + context + history）
        $messages = [];

        $messages[] = [
          'role' => 'system',
          'content' =>
"あなたは読書支援AIです。回答は日本語で、根拠がある場合は「どの情報（highlights/chunks）に基づくか」を短く添えてください。
不確かな場合は推測と断り、必要なら質問して確認してください。",
        ];

        // RAG最小：contextを1発で渡す（次フェーズで citations 等）
        $messages[] = [
          'role' => 'system',
          'content' => "以下は本の関連情報です。回答に活用してください。\n\n" . $bookContext,
        ];

        foreach ($recent as $m) {
          $messages[] = [
            'role' => $m->role, // user / assistant
            'content' => $m->content,
          ];
        }

        // AI応答
        $answer = $client->chat($messages);

        // assistant保存
        BookMessage::create([
          'book_thread_id' => $thread->id,
          'user_id' => $userId,
          'book_id' => $book->id,
          'role' => 'assistant',
          'content' => $answer,
          'char_length' => mb_strlen($answer),
        ]);
      });
    } catch (\Throwable $e) {
      return back()->with('error', $e->getMessage());
    }

    return back()->with('success', '送信しました');
  }
}
