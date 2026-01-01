<?php

namespace App\Services\Ai;

use App\Models\AiSummary;
use App\Models\Book;
use App\Models\BookChunk;
use App\Models\BookHighlight;
use Illuminate\Support\Str;
use OpenAI\Laravel\Facades\OpenAI;

class BookSummaryService
{
    /**
     * 「本の要約を生成して保存する」ユースケース
     */
    public function generateAndStore(Book $book, string $userId): AiSummary
    {
        // ① 材料収集（最小で確実に動く形）
        $highlights = $this->loadHighlights(bookId: (string) $book->id, userId: $userId);
        $chunks     = $this->loadChunks(bookId: (string) $book->id);

        // ② プロンプト構築
        $prompt = $this->buildPrompt(
            title: (string) $book->title,
            highlights: $highlights,
            chunks: $chunks
        );

        // ③ OpenAI 呼び出し
        $summaryText = $this->callOpenAi(prompt: $prompt);

        // ④ 保存
        return AiSummary::create([
            'id'          => (string) Str::uuid(),   // idがUUIDでないなら削除してOK
            'book_id'     => (string) $book->id,
            'user_id'     => $userId,
            'content'     => $summaryText,
            'char_length' => mb_strlen($summaryText),
        ]);
    }

    /**
     * ハイライト取得（上限をかけてトークン爆発を防ぐ）
     */
    private function loadHighlights(string $bookId, string $userId): array
    {
        return BookHighlight::query()
            ->where('book_id', $bookId)
            ->where('user_id', $userId)
            ->orderBy('created_at')
            ->limit(30)
            ->pluck('content')
            ->filter()
            ->values()
            ->toArray();
    }

    /**
     * 本文チャンク取得（最初は少量でOK。後でRAG化する）
     */
    private function loadChunks(string $bookId): array
    {
        return BookChunk::query()
            ->where('book_id', $bookId)
            ->orderBy('created_at')
            ->limit(10)
            ->pluck('content')
            ->filter()
            ->values()
            ->toArray();
    }

    /**
     * プロンプト：要約の「フォーマット」をここで固定する
     */
    private function buildPrompt(string $title, array $highlights, array $chunks): string
    {
        $highlightText = $highlights
            ? "- " . implode("\n- ", $highlights)
            : "(ハイライトなし)";

        $chunkText = $chunks
            ? implode("\n\n", $chunks)
            : "(本文なし)";

        return <<<PROMPT
あなたは読書支援の要約アシスタントです。

対象書籍：{$title}

【ユーザーのハイライト】
{$highlightText}

【本文（抜粋）】
{$chunkText}

この情報だけを根拠に、次の形式で日本語で要約してください。

### 1. 結論（3行）
### 2. 主張の骨子（箇条書き5〜8個）
### 3. 重要概念（用語：説明）
### 4. 読者が得る学び（3つ）
### 5. 次に深掘りする問い（2つ）

注意：
- 根拠がないことは断定しない
- 内容が不足している場合は「不足している」と明記する
PROMPT;
    }

    /**
     * OpenAI呼び出し（モデルや温度はここで管理）
     */
    private function callOpenAi(string $prompt): string
    {
        $res = OpenAI::chat()->create([
            'model' => config('services.openai.summary_model', 'gpt-4o-mini'),
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'あなたは厳密で読みやすい要約を作ります。',
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
            'temperature' => 0.3,
        ]);

        $text = $res->choices[0]->message->content ?? '';

        return trim($text);
    }
}
