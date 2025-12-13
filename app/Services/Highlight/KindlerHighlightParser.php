<?php

namespace App\Services\Highlight;

class KindleHighlightParser
{
    /**
     * @return array<int, array{
     *   source:string,
     *   title_raw:?string,
     *   location:?string,
     *   page:?string,
     *   highlighted_at:?string,
     *   content:string,
     *   content_hash:string
     * }>
     */
    public function parse(string $raw): array
    {
        $raw = str_replace(["\r\n", "\r"], "\n", $raw);

        // ざっくり判定：My Clippings は "==========" が区切りとして出ることが多い
        if (str_contains($raw, "==========")) {
            return $this->parseMyClippings($raw);
        }

        // それ以外は「メール/共有テキスト」想定（後で分岐追加）
        return $this->parseEmailLike($raw);
    }

    private function parseMyClippings(string $raw): array
    {
        $blocks = array_filter(array_map('trim', explode("==========", $raw)));
        $items = [];

        foreach ($blocks as $b) {
            $lines = array_values(array_filter(array_map('trim', explode("\n", $b)), fn($v) => $v !== ''));

            // v1: 先頭 = タイトル（著者まで混ざることあり）
            $titleRaw = $lines[0] ?? null;

            // v1: 本文は最後の塊（雑でもまず動かす）
            $content = end($lines);
            if (!is_string($content) || trim($content) === '') continue;

            $items[] = [
                'source' => 'kindle_clippings',
                'title_raw' => $titleRaw,
                'location' => null,
                'page' => null,
                'highlighted_at' => null,
                'content' => $content,
                'content_hash' => HighlightHasher::hash($content),
            ];
        }

        return $items;
    }

    private function parseEmailLike(string $raw): array
    {
        // v1: 「空行2つ」で区切られている前提で雑に抽出
        $chunks = preg_split("/\n{2,}/u", trim($raw)) ?: [];
        $items = [];

        // v1: titleは先頭行から推測（無理ならnull）
        $titleRaw = null;
        if (isset($chunks[0]) && mb_strlen($chunks[0]) < 80) {
            $titleRaw = trim($chunks[0]);
        }

        foreach ($chunks as $c) {
            $c = trim($c);
            if ($c === '' || mb_strlen($c) < 20) continue; // 短すぎる塊は除外（v1）

            $items[] = [
                'source' => 'kindle_email',
                'title_raw' => $titleRaw,
                'location' => null,
                'page' => null,
                'highlighted_at' => null,
                'content' => $c,
                'content_hash' => HighlightHasher::hash($c),
            ];
        }

        return $items;
    }
}
