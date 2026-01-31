<?php

namespace App\Enums;

/**
 * AIジョブのタイプ
 * 
 * 将来的に非同期処理を実装する際に使用
 */
enum AiJobType: string
{
    case SUMMARY = 'summary';
    case CHAT_RESPONSE = 'chat_response';

    /**
     * すべてのタイプを配列で取得
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * タイプの表示名を取得
     */
    public function label(): string
    {
        return match ($this) {
            self::SUMMARY => '要約生成',
            self::CHAT_RESPONSE => 'チャット応答',
        };
    }
}
