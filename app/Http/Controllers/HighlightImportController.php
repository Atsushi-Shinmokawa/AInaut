<?php

namespace App\Http\Controllers;

use App\Models\BookHighlight;
use App\Services\Highlight\KindleHighlightParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

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
            'items.*.content' => ['required','string'],
            'items.*.content_hash' => ['required','string','size:64'],
        ]);

        $userId = $request->user()->id;
        $inserted = 0;

        foreach ($data['items'] as $it) {
            try {
                BookHighlight::create([
                    'user_id' => $userId,
                    'book_id' => null,
                    'source' => $it['source'],
                    'title_raw' => $it['title_raw'] ?? null,
                    'content' => $it['content'],
                    'content_hash' => $it['content_hash'],
                    'location' => null,
                    'page' => null,
                    'highlighted_at' => null,
                ]);
                $inserted++;
            } catch (\Illuminate\Database\QueryException $e) {
                // content_hash UNIQUE の重複はスキップ（v1）
                continue;
            }
        }

        return redirect()->route('imports.kindle.create')
            ->with('success', "{$inserted}件のハイライトを取り込みました。");
    }
}
