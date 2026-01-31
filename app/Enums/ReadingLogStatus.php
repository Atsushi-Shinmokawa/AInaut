<?php

namespace App\Enums;

/**
 * 読書ログのステータス
 */
enum ReadingLogStatus: string
{
    case WANT_TO_READ = 'want_to_read';
    case READING = 'reading';
    case COMPLETED = 'completed';

    /**
     * すべてのステータスを配列で取得
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * ステータスの表示名を取得
     */
    public function label(): string
    {
        return match ($this) {
            self::WANT_TO_READ => '読みたい',
            self::READING => '読んでいる',
            self::COMPLETED => '読了',
        };
    }
}
