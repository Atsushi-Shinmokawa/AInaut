<?php

namespace App\Services;

use App\Enums\ReadingLogStatus;
use App\Models\BookHighlight;
use App\Models\ReadingLog;
use App\Models\ReadingNote;

class DashboardService
{
    /**
     * 読書統計（読了・読書中・読みたい）を取得
     */
    public function getStats(string $userId): array
    {
        $row = ReadingLog::where('user_id', $userId)
            ->selectRaw('
                COUNT(CASE WHEN status = ? THEN 1 END) as completed_count,
                COUNT(CASE WHEN status = ? THEN 1 END) as reading_count,
                COUNT(CASE WHEN status = ? THEN 1 END) as want_to_read_count
            ', [
                ReadingLogStatus::COMPLETED->value,
                ReadingLogStatus::READING->value,
                ReadingLogStatus::WANT_TO_READ->value,
            ])
            ->first();

        return [
            'completed' => (int) ($row->completed_count ?? 0),
            'reading' => (int) ($row->reading_count ?? 0),
            'want_to_read' => (int) ($row->want_to_read_count ?? 0),
        ];
    }

    /**
     * 最近更新した読書ログ（本）を取得（表紙・タイトル・著者・ステータス）
     */
    public function getRecentReadingLogs(string $userId, int $limit = 6): array
    {
        $logs = ReadingLog::where('user_id', $userId)
            ->with(['book', 'book.author'])
            ->orderBy('updated_at', 'desc')
            ->limit($limit)
            ->get();

        return $logs->map(function (ReadingLog $log) {
            $book = $log->book;
            return [
                'id' => $log->id,
                'status' => $log->status->value,
                'status_label' => $log->status->label(),
                'updated_at' => $log->updated_at->format('Y-m-d'),
                'book' => [
                    'id' => $book->id,
                    'title' => $book->title,
                    'cover_url' => $book->cover_url,
                    'author_name' => $book->author?->name ?? null,
                ],
            ];
        })->all();
    }

    /**
     * 最近の読書メモを取得（読書ログ経由でユーザー絞り）
     */
    public function getRecentNotes(string $userId, int $limit = 5): array
    {
        $notes = ReadingNote::whereHas('readingLog', fn ($q) => $q->where('user_id', $userId))
            ->with(['readingLog.book', 'readingLog.book.author'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return $notes->map(function (ReadingNote $note) {
            $log = $note->readingLog;
            $book = $log?->book;
            return [
                'id' => $note->id,
                'content' => \Illuminate\Support\Str::limit($note->content, 80),
                'page_number' => $note->page_number,
                'created_at' => $note->created_at->format('Y-m-d H:i'),
                'book_title' => $book?->title ?? '—',
                'book_id' => $book?->id,
                'reading_log_id' => $log?->id,
            ];
        })->all();
    }

    /**
     * 最近のハイライトを取得
     */
    public function getRecentHighlights(string $userId, int $limit = 5): array
    {
        $highlights = BookHighlight::where('user_id', $userId)
            ->with('book')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return $highlights->map(function (BookHighlight $h) {
            $book = $h->book;
            return [
                'id' => $h->id,
                'content' => \Illuminate\Support\Str::limit($h->content, 80),
                'page' => $h->page,
                'created_at' => $h->created_at?->format('Y-m-d H:i') ?? null,
                'book_title' => $book?->title ?? $h->title_raw ?? '—',
                'book_id' => $book?->id,
            ];
        })->all();
    }

    /**
     * ダッシュボード用に全データをまとめて返す
     */
    public function getDashboardData(string $userId): array
    {
        return [
            'stats' => $this->getStats($userId),
            'recent_reading_logs' => $this->getRecentReadingLogs($userId),
            'recent_notes' => $this->getRecentNotes($userId),
            'recent_highlights' => $this->getRecentHighlights($userId),
        ];
    }
}
