<?php

namespace App\Enums;

/**
 * チャットメッセージのロール
 * 
 * OpenAI APIのメッセージロールに対応
 */
enum BookMessageRole: string
{
    case USER = 'user';
    case ASSISTANT = 'assistant';
    case SYSTEM = 'system';

    /**
     * すべてのロールを配列で取得
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * OpenAI API用の配列形式に変換
     */
    public function toOpenAiFormat(): string
    {
        return $this->value;
    }
}
