<?php

namespace App\Services\Ai;

use App\Models\AiSummary;
use App\Models\Book;
use App\Services\Chat\BookContextBuilder;
use App\Services\Chat\OpenAiChatClient;

class BookSummaryService
{
    public function __construct(
        private OpenAiChatClient $openAi,
        private BookContextBuilder $contextBuilder,
    ) {}

    /**
     * 本のデータ（highlights/chunks）を材料に要約を生成し、DBへ保存
     */
    public function generateAndSave(Book $book, string $userId): AiSummary
    {
        // 本コンテキスト（highlights + chunks）をまとめて作る（要約は10万文字まで渡す）
        $context = $this->contextBuilder->build($book, maxChars: 100_000);

        $system = <<<SYS
あなたは「厳密で読みやすい要約」を作る編集者です。
条件:
- 事実の飛躍をしない（根拠のない断定を避ける）
- 箇条書きを活用して読みやすく
- 重要語はなるべく原文の表現を維持
- まず全体像→次にポイント→最後に短い結論
SYS;

        $user = <<<USR
以下の資料をもとに、この本の要約を作ってください。

【出力フォーマット】
1) 3行まとめ（超短縮）
2) 要点（箇条書き 5〜10個）
3) キーワード（5〜12個）
4) もう一歩深い読み（この本から得られる示唆を3つ）

【資料】
{$context}
USR;

        $messages = [
            ['role' => \App\Enums\BookMessageRole::SYSTEM->value, 'content' => $system],
            ['role' => \App\Enums\BookMessageRole::USER->value, 'content' => $user],
        ];

        // 要約用モデル（未設定なら model と同じ）
        $model = config('services.openai.summary_model', config('services.openai.model', 'gpt-4o-mini'));

        $content = $this->openAi->chat(
            $messages,
            $model,
            0.3 // 要約はブレを減らす
        );

        return AiSummary::create([
            'book_id' => $book->id,
            'user_id' => $userId,
            'model_name' => $model,
            'content' => $content,
            'context_type' => 'general',
            'meta' => [
                'source' => 'highlights+chunks',
                'max_chars' => 100_000,
            ],
        ]);
    }
}
