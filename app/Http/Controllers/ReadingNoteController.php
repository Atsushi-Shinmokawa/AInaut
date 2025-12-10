<?php

namespace App\Http\Controllers;   // ★ これ必須！

use App\Http\Requests\StoreReadingNoteRequest;
use App\Models\ReadingLog;
use App\Models\ReadingNote;
use Illuminate\Http\RedirectResponse;

class ReadingNoteController extends Controller
{
    public function store(
        StoreReadingNoteRequest $request,
        ReadingLog $readingLog
    ): RedirectResponse {
        // $this->authorize('update', $readingLog); // あれば

        $readingLog->readingNotes()->create(
            $request->validated()
        );

        return back()->with('success', 'メモを追加しました。');
    }

    public function destroy(
        ReadingLog $readingLog,
        ReadingNote $readingNote
    ): RedirectResponse {
        // $this->authorize('update', $readingLog); // あれば

        if ($readingNote->reading_log_id !== $readingLog->id) {
            abort(404);
        }

        $readingNote->delete();

        return back()->with('success', 'メモを削除しました。');
    }
}
