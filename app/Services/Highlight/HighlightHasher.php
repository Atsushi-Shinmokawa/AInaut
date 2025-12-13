<?php

namespace App\Services\Highlight;

class HighlightHasher
{
    public static function normalize(string $text): string
    {
        $t = trim($text);

        // 全角スペース→半角
        $t = str_replace("\xE3\x80\x80", ' ', $t);

        // 改行コード統一
        $t = str_replace(["\r\n", "\r"], "\n", $t);

        // 連続空白/連続改行を圧縮
        $t = preg_replace("/[ \t]+/u", ' ', $t);
        $t = preg_replace("/\n{3,}/u", "\n\n", $t);

        return trim($t);
    }

    public static function hash(string $content): string
    {
        return hash('sha256', self::normalize($content));
    }
}
