<?php

namespace App\Services\Chat;

/**
 * チャットで参照する本の「範囲」を表す DTO。
 * ユーザー発話から ScopeParser が解釈して生成する。
 */
final readonly class ChatScope
{
    public const string TYPE_DEFAULT = 'default';
    public const string TYPE_WHOLE = 'whole';
    public const string TYPE_FIRST_HALF = 'first_half';
    public const string TYPE_SECOND_HALF = 'second_half';
    public const string TYPE_OPENING = 'opening';
    public const string TYPE_ENDING = 'ending';

    public function __construct(
        public string $scopeType = self::TYPE_DEFAULT,
        public ?int $chapterNumber = null,
    ) {}

    public function isDefault(): bool
    {
        return $this->scopeType === self::TYPE_DEFAULT;
    }

    public function isWhole(): bool
    {
        return $this->scopeType === self::TYPE_WHOLE;
    }

    public function isSecondHalf(): bool
    {
        return $this->scopeType === self::TYPE_SECOND_HALF;
    }

    public function isFirstHalf(): bool
    {
        return $this->scopeType === self::TYPE_FIRST_HALF;
    }

    public function isOpening(): bool
    {
        return $this->scopeType === self::TYPE_OPENING;
    }

    public function isEnding(): bool
    {
        return $this->scopeType === self::TYPE_ENDING;
    }
}
