<?php

namespace App\Enums;

/**
 * AIジョブのステータス
 * 
 * 将来的に非同期処理を実装する際に使用
 */
enum AiJobStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';

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
            self::PENDING => '待機中',
            self::PROCESSING => '処理中',
            self::COMPLETED => '完了',
            self::FAILED => '失敗',
        };
    }

    /**
     * 完了状態かどうか
     */
    public function isCompleted(): bool
    {
        return $this === self::COMPLETED;
    }

    /**
     * 失敗状態かどうか
     */
    public function isFailed(): bool
    {
        return $this === self::FAILED;
    }

    /**
     * 処理中かどうか（待機中または処理中）
     */
    public function isProcessing(): bool
    {
        return in_array($this, [self::PENDING, self::PROCESSING], true);
    }
}
