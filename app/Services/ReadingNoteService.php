<?php

namespace App\Services;

use App\Exceptions\AppServiceNotFoundException;
use App\Models\ReadingLog;
use App\Models\ReadingNote;

class ReadingNoteService
{
    /**
     * 読書ノートを作成
     */
    public function store(ReadingLog $readingLog, array $data): ReadingNote
    {
        return $readingLog->readingNotes()->create($data);
    }

    /**
     * 読書ノートを削除
     */
    public function destroy(ReadingLog $readingLog, ReadingNote $readingNote): void
    {
        // 親子関係の整合性チェック
        if ($readingNote->reading_log_id !== $readingLog->id) {
            throw new AppServiceNotFoundException('読書ノートが見つかりませんでした。');
        }

        $readingNote->delete();
    }
}
