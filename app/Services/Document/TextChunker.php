<?php

namespace App\Services\Document;

class TextChunker
{
    /**
     * @return array<int, string> chunks
     */
    public function chunk(string $text, int $min = 800, int $max = 1200): array
    {
        $text = $this->normalize($text);

        // 段落単位っぽく分ける（空行で分割）
        $paragraphs = preg_split("/\n{2,}/u", $text) ?: [];
        $chunks = [];
        $buf = '';

        foreach ($paragraphs as $p) {
            $p = trim($p);
            if ($p === '') continue;

            // 段落が長すぎる場合は強制分割
            if (mb_strlen($p) > $max) {
                // いま貯めてるbufがあれば先に確定
                if (trim($buf) !== '') {
                    $chunks[] = trim($buf);
                    $buf = '';
                }
                $chunks = array_merge($chunks, $this->splitHard($p, $max));
                continue;
            }

            $candidate = ($buf === '') ? $p : ($buf . "\n\n" . $p);

            if (mb_strlen($candidate) <= $max) {
                $buf = $candidate;
                continue;
            }

            // max超える → bufを確定して新しく開始
            if (trim($buf) !== '') {
                $chunks[] = trim($buf);
            }
            $buf = $p;
        }

        if (trim($buf) !== '') {
            $chunks[] = trim($buf);
        }

        // 末尾がmin未満なら、前と結合できる場合は結合
        $chunks = $this->mergeSmallTail($chunks, $min, $max);

        return $chunks;
    }

    private function normalize(string $text): string
    {
        // 改行統一
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        // 連続空白行を整理（完全に潰しすぎない）
        $text = preg_replace("/[ \t]+\n/u", "\n", $text) ?? $text;
        $text = trim($text);
        return $text;
    }

    /**
     * @return array<int, string>
     */
    private function splitHard(string $p, int $max): array
    {
        $out = [];
        $len = mb_strlen($p);
        $i = 0;

        while ($i < $len) {
            $out[] = mb_substr($p, $i, $max);
            $i += $max;
        }

        return $out;
    }

    /**
     * @param array<int, string> $chunks
     * @return array<int, string>
     */
    private function mergeSmallTail(array $chunks, int $min, int $max): array
    {
        $n = count($chunks);
        if ($n <= 1) return $chunks;

        $last = $chunks[$n - 1];
        if (mb_strlen($last) >= $min) return $chunks;

        $prev = $chunks[$n - 2];
        $candidate = $prev . "\n\n" . $last;

        if (mb_strlen($candidate) <= $max) {
            $chunks[$n - 2] = $candidate;
            array_pop($chunks);
        }

        return $chunks;
    }
}
