<?php

namespace App\Services;

use App\Models\ReadingLog;
use App\Models\User;
use Illuminate\Support\Collection;

class ReadingLogService
{
    /**
     * ログインユーザーの読書ログ一覧を取得
     */
    public function listForUser(User $user): Collection
    {
        return ReadingLog::query()
            ->with('book')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * 本棚に追加 or 既存ログのステータス更新
     */
    public function storeOrUpdate(User $user, array $data): ReadingLog
    {
        $status = $data['status'] ?? ReadingLog::STATUS_WANT_TO_READ;

        $log = ReadingLog::firstOrNew([
            'user_id' => $user->id,
            'book_id' => $data['book_id'],
        ]);

        [$startedAt, $completedAt] = $this->calcDates($log, $status);

        $log->fill([
            'status'       => $status,
            'started_at'   => $startedAt,
            'completed_at' => $completedAt,
        ]);

        $log->save();

        return $log;
    }

    /**
     * ステータスだけ更新（マイ本棚のステータス切り替え用）
     */
    public function updateStatus(ReadingLog $log, string $status): ReadingLog
    {
        [$startedAt, $completedAt] = $this->calcDates($log, $status);

        $log->update([
            'status'       => $status,
            'started_at'   => $startedAt,
            'completed_at' => $completedAt,
        ]);

        return $log;
    }

    /**
     * ログ削除
     */
    public function delete(ReadingLog $log): void
    {
        $log->delete();
    }

    /**
     * ステータスに応じて started_at / completed_at をよしなに調整
     */
    private function calcDates(ReadingLog $log, string $status): array
    {
        $today       = now()->toDateString();
        $startedAt   = $log->started_at;
        $completedAt = $log->completed_at;

        switch ($status) {
            case ReadingLog::STATUS_WANT_TO_READ:
                $startedAt   = null;
                $completedAt = null;
                break;

            case ReadingLog::STATUS_READING:
                if (! $startedAt) {
                    $startedAt = $today;
                }
                $completedAt = null;
                break;

            case ReadingLog::STATUS_COMPLETED:
                if (! $startedAt) {
                    $startedAt = $today;
                }
                if (! $completedAt) {
                    $completedAt = $today;
                }
                break;
        }

        return [$startedAt, $completedAt];
    }
}
