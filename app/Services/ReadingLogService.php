<?php

namespace App\Services;

use App\Enums\ReadingLogStatus;
use App\Models\ReadingLog;
use App\Models\User;
use App\Resources\ReadingLogResource;
use Illuminate\Support\Collection;

class ReadingLogService
{
    /**
     * ログインユーザーの読書ログ一覧を取得（Inertia用に整形済み）
     */
    public function list(User $user): array
    {
        $readingLogs = ReadingLog::query()
            ->with(['book.author', 'readingNotes'])
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        return [
            'readingLogs' => ReadingLogResource::collection($readingLogs)->resolve(),
            'statuses' => ReadingLogStatus::values(),
        ];
    }

    /**
     * 本棚に追加 or 既存ログのステータス更新
     */
    public function storeOrUpdate(User $user, array $data): ReadingLog
    {
        $status = isset($data['status'])
            ? ReadingLogStatus::from($data['status'])
            : ReadingLogStatus::WANT_TO_READ;

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
    public function updateStatus(ReadingLog $log, ReadingLogStatus|string $status): ReadingLog
    {
        // 文字列の場合はEnumに変換
        if (is_string($status)) {
            $status = ReadingLogStatus::from($status);
        }

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
    private function calcDates(ReadingLog $log, ReadingLogStatus $status): array
    {
        $today       = now()->toDateString();
        $startedAt   = $log->started_at;
        $completedAt = $log->completed_at;

        return match ($status) {
            ReadingLogStatus::WANT_TO_READ => [null, null],
            ReadingLogStatus::READING => [
                $startedAt ?: $today,
                null,
            ],
            ReadingLogStatus::COMPLETED => [
                $startedAt ?: $today,
                $completedAt ?: $today,
            ],
        };
    }
}
