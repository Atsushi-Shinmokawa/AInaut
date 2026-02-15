<?php

namespace App\Services\Chat;

use App\Enums\BookMessageRole;
use App\Enums\ChatCharacter;
use App\Models\Book;
use App\Models\BookMessage;
use App\Models\BookThread;
use App\Models\UserCharacterProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookChatService
{
    private const int CHAT_CONTEXT_MAX_CHARS = 9000;
    private const int CHAT_TOP_K_CHUNKS = 80;

    public function __construct(
        private BookContextBuilder $contextBuilder,
        private OpenAiChatClient $client,
        private ScopeParser $scopeParser,
        private ChunkRetriever $chunkRetriever,
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

        // 2) 文脈を組み立て（ユーザー発話に応じたチャンク取得）
        $recent = BookMessage::where('book_thread_id', $thread->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->reverse()
            ->values();

        $bookContext = $this->buildBookContextForChat($book, $content);

        $messages = [];

        $characterEnum = ChatCharacter::tryFrom($thread->character ?? '') ?? ChatCharacter::ZUNDAMON;

        $profile = UserCharacterProfile::where('user_id', $userId)
            ->where('character', $characterEnum->value)
            ->first();

        // 1) キャラ指示を「最初の system」に単独で渡す（OpenAI でキャラが効きやすくする）
        $messages[] = [
            'role' => BookMessageRole::SYSTEM->value,
            'content' => $characterEnum->basePrompt(),
        ];

        $supportParts = $this->buildSupportPartsForChat($profile);
        $messages[] = [
            'role' => BookMessageRole::SYSTEM->value,
            'content' => implode("\n\n", $supportParts),
        ];

        $messages[] = [
            'role' => BookMessageRole::SYSTEM->value,
            'content' => "以下は本の関連情報です。回答に活用してください。\n\n" . $bookContext,
        ];

        if ($characterEnum === ChatCharacter::ZUNDAMON) {
            $messages[] = [
                'role' => BookMessageRole::SYSTEM->value,
                'content' => '【厳守】次の返答は必ずずんだもんの口調で書くこと。語尾に「なのだ」「のだ」を入れる。「です・ます」だけの普通の敬語では書かない。',
            ];
        }

        $hasAssistantInRecent = $recent->contains(fn ($m) => $m->role === BookMessageRole::ASSISTANT);
        if (!$hasAssistantInRecent && $characterEnum === ChatCharacter::ZUNDAMON) {
            $messages[] = ['role' => 'user', 'content' => 'こんにちは'];
            $messages[] = ['role' => 'assistant', 'content' => 'おっ、こんにちはなのだ！僕はずんだもんなのだ、この本のこと何でも聞いてなのだ！'];
            $messages[] = ['role' => 'user', 'content' => 'ずんだもん元気？'];
            $messages[] = ['role' => 'assistant', 'content' => 'うん、元気なのだ、ありがとう！君はどうなのだ？'];
            $messages[] = ['role' => 'user', 'content' => 'ずんだもんだよね？'];
            $messages[] = ['role' => 'assistant', 'content' => 'うん、僕はずんだもんなのだ！'];
        }

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
        Log::info('BookChatService::generateResponseForUserMessage start', ['user_message_id' => $userMessage->id]);

        $thread = $userMessage->thread;
        $book = $userMessage->book;

        if (!$thread || !$book) {
            Log::warning('BookChatService::generateResponseForUserMessage skip', ['thread' => (bool) $thread, 'book' => (bool) $book]);
            return;
        }

        $recent = BookMessage::where('book_thread_id', $thread->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->reverse()
            ->values();

        $bookContext = $this->buildBookContextForChat($book, $userMessage->content);

        $messages = [];

        $characterEnum = ChatCharacter::tryFrom($thread->character ?? '') ?? ChatCharacter::ZUNDAMON;
        $profile = UserCharacterProfile::where('user_id', $userMessage->user_id)
            ->where('character', $characterEnum->value)
            ->first();

        // 1) キャラ指示を「最初の system」に単独で渡す（OpenAI でキャラが効きやすくする）
        $messages[] = [
            'role' => BookMessageRole::SYSTEM->value,
            'content' => $characterEnum->basePrompt(),
        ];

        $supportParts = $this->buildSupportPartsForChat($profile);
        $messages[] = [
            'role' => BookMessageRole::SYSTEM->value,
            'content' => implode("\n\n", $supportParts),
        ];

        $messages[] = [
            'role' => BookMessageRole::SYSTEM->value,
            'content' => "以下は本の関連情報です。回答に活用してください。\n\n" . $bookContext,
        ];

        if ($characterEnum === ChatCharacter::ZUNDAMON) {
            $messages[] = [
                'role' => BookMessageRole::SYSTEM->value,
                'content' => '【厳守】次の返答は必ずずんだもんの口調で書くこと。語尾に「なのだ」「のだ」を入れる。「です・ます」だけの普通の敬語では書かない。',
            ];
        }

        // 会話に assistant がまだ1件もないとき、ずんだもんの口調の例を3往復入れる（OpenAI が口調を真似しやすくする）
        $hasAssistantInRecent = $recent->contains(fn ($m) => $m->role === BookMessageRole::ASSISTANT);
        if (!$hasAssistantInRecent && $characterEnum === ChatCharacter::ZUNDAMON) {
            $messages[] = ['role' => 'user', 'content' => 'こんにちは'];
            $messages[] = ['role' => 'assistant', 'content' => 'おっ、こんにちはなのだ！僕はずんだもんなのだ、この本のこと何でも聞いてなのだ！'];
            $messages[] = ['role' => 'user', 'content' => 'ずんだもん元気？'];
            $messages[] = ['role' => 'assistant', 'content' => 'うん、元気なのだ、ありがとう！君はどうなのだ？'];
            $messages[] = ['role' => 'user', 'content' => 'ずんだもんだよね？'];
            $messages[] = ['role' => 'assistant', 'content' => 'うん、僕はずんだもんなのだ！'];
        }

        foreach ($recent as $m) {
            $messages[] = [
                'role' => $m->role->value,
                'content' => $m->content,
            ];
        }

        $modelName = null;
        if ($thread->model) {
            $row = collect(config('services.openai.chat_models', []))->firstWhere('id', $thread->model);
            $modelName = $row['model'] ?? null;
        }
        $answer = $this->client->chat($messages, $modelName);

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

    /**
     * チャット用：ユーザー発話に応じたチャンクを取得し、本の文脈文字列を組み立てる。
     */
    private function buildBookContextForChat(Book $book, string $userQuery): string
    {
        $scope = $this->scopeParser->parse($userQuery);
        $chunks = $this->chunkRetriever->retrieve(
            $book,
            $scope,
            topK: self::CHAT_TOP_K_CHUNKS,
            withNeighbors: 1,
        );

        if ($chunks->isEmpty()) {
            return $this->contextBuilder->build($book, self::CHAT_CONTEXT_MAX_CHARS);
        }

        return $this->contextBuilder->buildFromChunks($book, $chunks, self::CHAT_CONTEXT_MAX_CHARS);
    }

    /**
     * チャット用：読書支援・根拠明示・根拠不足時の指示を含む support パーツを返す。
     *
     * @param  UserCharacterProfile|null  $profile
     * @return array<int, string>
     */
    private function buildSupportPartsForChat(?UserCharacterProfile $profile): array
    {
        $parts = [];
        if ($profile) {
            $parts[] = $profile->toPromptText();
        }
        $parts[] = '読書支援では、回答は日本語で行うこと。'
            . '根拠がある場合は、どの情報（highlights または【chunk N】）に基づくかを必ず明示すること。'
            . '参照データにない内容については「本文の根拠が不足しています」と断り、推測で答える場合はその旨を明記すること。'
            . '必要なら質問して確認すること。必ず上記のキャラの口調を崩さずに応答すること。';

        return $parts;
    }
}
