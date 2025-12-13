<?php

namespace App\Http\Controllers;

use App\Models\BookHighlight;
use App\Services\Highlight\KindleHighlightParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Database\QueryException;

class HighlightImportController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Imports/Kindle/Create');
    }

    public function preview(Request $request, KindleHighlightParser $parser): Response
    {
        $data = $request->validate([
            'raw_text' => ['required','string','min:20'],
        ]);

        $items = $parser->parse($data['raw_text']);

        return Inertia::render('Imports/Kindle/Preview', [
            'raw_text' => $data['raw_text'],
            'items' => array_slice($items, 0, 200), // v1の安全策
            'count' => count($items),
        ]);
    }



public function commit(Request $request): RedirectResponse
{
    $data = $request->validate([
        'items' => ['required','array','min:1'],
        'items.*.source' => ['required','string'],
        'items.*.title_raw' => ['nullable','string'],
        'items.*.location' => ['nullable','string'],
        'items.*.page' => ['nullable','string'],
        'items.*.highlighted_at' => ['nullable','string'],
        'items.*.content' => ['required','string'],
        // content_hash は受け取ってもいいが信用しない
        'items.*.content_hash' => ['nullable','string'],
    ]);

    $userId = $request->user()->id;

    $saved = 0;
    $skipped = 0;
    $failed = 0;

    foreach ($data['items'] as $it) {
        $content = $it['content'];
        $hash = \App\Services\Highlight\HighlightHasher::hash($content);

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

    // 何かしら失敗があったら warning 扱いもあり
    $message = "保存: {$saved}件 / 重複スキップ: {$skipped}件";
    if ($failed > 0) {
        $message .= " / 失敗: {$failed}件";
    }

    return redirect()
        ->route('imports.kindle.create')
        ->with('status', [
            'saved' => $saved,
            'skipped' => $skipped,
            'failed' => $failed,
            'message' => $message,
        ]);
}

}
