<?php

namespace App\Http\Controllers;

use App\Models\BookHighlight;
use App\Services\BookHighlightService;
use App\Services\Highlight\KindleHighlightParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BookHighlightController extends Controller
{
    public function __construct(
        private readonly BookHighlightService $bookHighlightService,
    ) {}

    /**
     * Kindleハイライトインポート画面を表示
     */
    public function importCreate(): Response
    {
        return Inertia::render('Imports/Kindle/Create');
    }

    /**
     * Kindleハイライトインポートのプレビュー
     */
    public function importPreview(Request $request, KindleHighlightParser $parser): Response
    {
        $data = $request->validate([
            'raw_text' => ['required', 'string', 'min:20'],
        ]);

        try {
            $props = $this->bookHighlightService->importPreview($data['raw_text'], $parser);
            return Inertia::render('Imports/Kindle/Preview', $props);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Kindleハイライトインポートの実行
     */
    public function importCommit(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.source' => ['required', 'string'],
            'items.*.title_raw' => ['nullable', 'string'],
            'items.*.location' => ['nullable', 'string'],
            'items.*.page' => ['nullable', 'string'],
            'items.*.highlighted_at' => ['nullable', 'string'],
            'items.*.content' => ['required', 'string'],
            'items.*.content_hash' => ['nullable', 'string'],
        ]);

        $userId = $request->user()->id;
        $result = $this->bookHighlightService->importCommit($data['items'], $userId);

        return redirect()
            ->route('imports.kindle.create')
            ->with('status', $result);
    }

    /**
     * ハイライトを削除
     */
    public function destroy(BookHighlight $highlight, Request $request): RedirectResponse
    {
        $this->bookHighlightService->destroy($highlight, $request->user()->id);

        return back()->with('success', 'ハイライトを削除しました');
    }

    /**
     * ハイライトを本に紐付け
     */
    public function attach(BookHighlight $highlight, Request $request): RedirectResponse
    {
        $data = $request->validate([
            'book_id' => ['required', 'uuid'],
        ]);

        $this->bookHighlightService->attach($highlight, $data['book_id']);

        return back()->with('success', 'ハイライトを本に紐付けました');
    }
}
