<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReadingNoteRequest;
use App\Models\ReadingLog;
use App\Models\ReadingNote;
use App\Services\ReadingNoteService;
use Illuminate\Http\RedirectResponse;

class ReadingNoteController extends Controller
{
    public function __construct(
        private readonly ReadingNoteService $readingNoteService,
    ) {}

    public function store(
        StoreReadingNoteRequest $request,
        ReadingLog $readingLog
    ): RedirectResponse {
        // $this->authorize('update', $readingLog); // あれば

        $this->readingNoteService->store(
            $readingLog,
            $request->validated()
        );

        return back()->with('success', 'メモを追加しました。');
    }

    public function destroy(
        ReadingLog $readingLog,
        ReadingNote $readingNote
    ): RedirectResponse {
        // $this->authorize('update', $readingLog); // あれば

        $this->readingNoteService->destroy($readingLog, $readingNote);

        return back()->with('success', 'メモを削除しました。');
    }
}
