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

        $titleRaw = $lines[0] ?? null;
        $metaLine = $lines[1] ?? null;

        // 本文: 3行目以降を全部結合（v1）
        $contentLines = array_slice($lines, 2);
        $content = trim(implode("\n", $contentLines));
        if ($content === '' || mb_strlen($content) < 5) continue;

        $items[] = [
            'source' => 'kindle_clippings',
            'title_raw' => $titleRaw,
            'location' => null, // v1: 後でメタから抽出してもOK
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
    $raw = trim($raw);
    if ($raw === '') return [];

    // 空行2つ以上で区切る（v1）
    $chunks = preg_split("/\n{2,}/u", $raw) ?: [];
    $chunks = array_values(array_filter(array_map('trim', $chunks), fn($v) => $v !== ''));

    if (count($chunks) === 0) return [];

    // 先頭塊が「タイトルっぽい」なら title にして items から除外
    $titleRaw = null;
    if (isset($chunks[0]) && mb_strlen($chunks[0]) <= 80 && !str_contains($chunks[0], "\n")) {
        $titleRaw = $chunks[0];
        array_shift($chunks);
    }

    $items = [];
    foreach ($chunks as $c) {
        $c = trim($c);

        // v1: 20文字未満は本文として弱いので捨てる
        if ($c === '' || mb_strlen($c) < 20) continue;

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
