<?php

namespace App\Services;

use App\Exceptions\AppServiceForbiddenException;
use App\Exceptions\AppServiceValidationException;
use App\Models\BookHighlight;
use App\Services\Highlight\HighlightHasher;
use App\Services\Highlight\KindleHighlightParser;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;

class BookHighlightService
{
    /**
     * Kindleハイライトインポートのプレビュー
     */
    public function importPreview(string $rawText, KindleHighlightParser $parser): array
    {
        try {
            $items = $parser->parse($rawText);
        } catch (\Throwable $e) {
            throw new AppServiceValidationException(
                '取り込み形式を認識できませんでした。Kindleのハイライト全文を貼り付けてください。'
            );
        }

        if (empty($items)) {
            throw new AppServiceValidationException(
                'ハイライトが1件も見つかりませんでした。貼り付け内容を確認してください。'
            );
        }

        return [
            'raw_text' => $rawText,
            'items' => array_slice($items, 0, 200), // v1の安全策
            'count' => count($items),
        ];
    }

    /**
     * Kindleハイライトインポートの実行
     */
    public function importCommit(array $items, string $userId): array
    {
        $saved = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($items as $it) {
            $content = $it['content'];
            $hash = HighlightHasher::hash($content);

            try {
                BookHighlight::create([
                    'user_id' => $userId,
                    'book_id' => null,
                    'source' => $it['source'],
                    'title_raw' => $it['title_raw'] ?? null,
                    'content' => $content,
                    'content_hash' => $hash,
                    'location' => $it['location'] ?? null,
                    'page' => $it['page'] ?? null,
                    'highlighted_at' => $it['highlighted_at'] ?? null,
                ]);
                $saved++;
            } catch (QueryException $e) {
                // UNIQUE違反だけスキップ
                $sqlState = $e->errorInfo[0] ?? null;
                $driverCode = $e->errorInfo[1] ?? null; // MySQLなら 1062 が多い

                if ($sqlState === '23000' || $driverCode === 1062) {
                    $skipped++;
                    continue;
                }

                // それ以外は失敗として数える（握りつぶさない）
                $failed++;
            } catch (\Throwable $e) {
                $failed++;
            }
        }

        $message = "{$saved}件保存しました（{$skipped}件は重複でスキップ）";
        if ($failed > 0) {
            $message .= "／{$failed}件は保存に失敗しました";
        }

        return [
            'saved' => $saved,
            'skipped' => $skipped,
            'failed' => $failed,
            'message' => $message,
        ];
    }

    /**
     * ハイライトを削除
     */
    public function destroy(BookHighlight $highlight, string $userId): void
    {
        if ($highlight->user_id !== $userId) {
            throw new AppServiceForbiddenException('このハイライトを削除する権限がありません。');
        }

        $highlight->delete();
    }

    /**
     * ハイライトを本に紐付け
     */
    public function attach(BookHighlight $highlight, string $bookId): void
    {
        $highlight->update([
            'book_id' => $bookId,
        ]);
    }
}
