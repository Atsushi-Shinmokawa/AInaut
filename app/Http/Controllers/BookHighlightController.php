<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookHighlightAttachRequest;
use App\Http\Requests\BookHighlightImportCommitRequest;
use App\Http\Requests\BookHighlightImportPreviewRequest;
use App\Models\BookHighlight;
use App\Services\BookHighlightService;
use App\Services\Highlight\KindleHighlightParser;
use Illuminate\Http\RedirectResponse;
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
    public function importPreview(BookHighlightImportPreviewRequest $request, KindleHighlightParser $parser): Response
    {
        try {
            $props = $this->bookHighlightService->importPreview($request->validated('raw_text'), $parser);
            return Inertia::render('Imports/Kindle/Preview', $props);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Kindleハイライトインポートの実行
     */
    public function importCommit(BookHighlightImportCommitRequest $request): RedirectResponse
    {
        $userId = $request->user()->id;
        $result = $this->bookHighlightService->importCommit($request->validated('items'), $userId);

        return redirect()
            ->route('imports.kindle.create')
            ->with('status', $result);
    }

    /**
     * ハイライトを削除
     */
    public function destroy(BookHighlight $highlight): RedirectResponse
    {
        $this->bookHighlightService->destroy($highlight, auth()->id());

        return back()->with('success', 'ハイライトを削除しました');
    }

    /**
     * ハイライトを本に紐付け
     */
    public function attach(BookHighlight $highlight, BookHighlightAttachRequest $request): RedirectResponse
    {
        $this->bookHighlightService->attach($highlight, $request->validated('book_id'));

        return back()->with('success', 'ハイライトを本に紐付けました');
    }
}
