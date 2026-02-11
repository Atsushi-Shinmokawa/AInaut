<?php

namespace App\Services\Document;

use App\Exceptions\AppServiceExternalApiException;
use App\Exceptions\AppServiceValidationException;
use Illuminate\Support\Facades\Http;

class AozoraFetcher
{
    /**
     * 青空文庫URLから本文テキストを取得する。
     * - URLが .html ならそのページを取得し、HTMLから本文を抽出
     * - URLが .txt ならそのまま取得
     * - それ以外（図書カード等）なら、HTMLから .html または .txt のリンクを探す（.html 優先）
     */
    public function fetchText(string $url): array
    {
        $url = trim($url);

        $this->assertAllowedHost($url);

        // 1) .html の直URL → HTML取得 → 本文抽出
        if (preg_match('/\.html(\?.*)?$/i', $url)) {
            $html = $this->get($url);
            $text = $this->extractTextFromHtml($html);
            return ['text' => $text, 'resolved_url' => $url];
        }

        // 2) .txt の直URL（従来どおり）
        if (preg_match('/\.txt(\?.*)?$/i', $url)) {
            $txt = $this->get($url);
            return ['text' => $txt, 'resolved_url' => $url];
        }

        // 3) 図書カード等 → .html を優先、なければ .txt
        $html = $this->get($url);
        $targetUrl = $this->extractHtmlUrl($html, $url) ?? $this->extractTxtUrl($html, $url);
        if (!$targetUrl) {
            throw new AppServiceValidationException(
                '青空文庫ページから XHTML(.html) または テキスト(.txt) のリンクが見つかりませんでした。'
            );
        }

        if (preg_match('/\.html(\?.*)?$/i', $targetUrl)) {
            $htmlContent = $this->get($targetUrl);
            $text = $this->extractTextFromHtml($htmlContent);
            return ['text' => $text, 'resolved_url' => $targetUrl];
        }

        $txt = $this->get($targetUrl);
        return ['text' => $txt, 'resolved_url' => $targetUrl];
    }

    private function get(string $url): string
    {
        $res = Http::timeout(20)
            ->retry(2, 300)
            ->withHeaders([
                'User-Agent' => 'AInaut/1.0 (+https://example.invalid)',
            ])
            ->get($url);

        if (!$res->successful()) {
            throw new AppServiceExternalApiException(
                '青空文庫からの取得に失敗しました。',
                $res->status(),
                '青空文庫',
                $url,
                null // レスポンスボディは通常不要
            );
        }

        // 青空のtxt/htmlはShift_JISのことがあるので、UTF-8に変換
        $body = $res->body();
        $body = $this->convertToUtf8($body);

        return $body;
    }

    private function convertToUtf8(string $body): string
    {
        if (mb_check_encoding($body, 'UTF-8')) {
            return $body;
        }
        $converted = @mb_convert_encoding($body, 'UTF-8', 'SJIS-win');
        return $converted ?: $body;
    }

    private function assertAllowedHost(string $url): void
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (!$host) {
            throw new AppServiceValidationException('URLが不正です。');
        }

        $allowed = ['www.aozora.gr.jp', 'aozora.gr.jp'];
        if (!in_array($host, $allowed, true)) {
            throw new AppServiceValidationException('青空文庫（aozora.gr.jp）のURLのみ許可しています。');
        }
    }

    private function extractHtmlUrl(string $html, string $baseUrl): ?string
    {
        $dom = new \DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));

        $links = $dom->getElementsByTagName('a');
        foreach ($links as $a) {
            /** @var \DOMElement $a */
            $href = $a->getAttribute('href');
            if (!$href) continue;
            if (!preg_match('/\.html(\?.*)?$/i', $href)) continue;

            return $this->resolveUrl($baseUrl, $href);
        }

        return null;
    }

    private function extractTxtUrl(string $html, string $baseUrl): ?string
    {
        $dom = new \DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));

        $links = $dom->getElementsByTagName('a');
        foreach ($links as $a) {
            /** @var \DOMElement $a */
            $href = $a->getAttribute('href');
            if (!$href) continue;
            if (!preg_match('/\.txt(\?.*)?$/i', $href)) continue;

            return $this->resolveUrl($baseUrl, $href);
        }

        return null;
    }

    /**
     * 青空文庫XHTMLから本文をプレーンテキストに抽出する。
     * body 内を走査し、ルビは rb のみ採用、ブロック要素で改行を入れる。
     */
    private function extractTextFromHtml(string $html): string
    {
        $dom = new \DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));

        $body = $dom->getElementsByTagName('body')->item(0);
        if (!$body) {
            return '';
        }

        $parts = [];
        $this->collectTextNodes($body, $parts);
        $text = implode('', $parts);

        // 改行・空白の正規化
        $text = preg_replace("/[ \t]+/u", ' ', $text);
        $text = preg_replace("/\n[ \t]+/u", "\n", $text);
        $text = preg_replace("/[ \t]+\n/u", "\n", $text);
        $text = preg_replace("/\n{3,}/u", "\n\n", $text);

        return trim($text);
    }

    /**
     * @param array<string> $parts
     */
    private function collectTextNodes(\DOMNode $node, array &$parts): void
    {
        if ($node->nodeType === XML_TEXT_NODE) {
            $parts[] = $node->textContent;
            return;
        }

        if ($node->nodeType !== XML_ELEMENT_NODE) {
            return;
        }

        $tag = strtolower($node->nodeName ?? '');
        $blockTags = ['p', 'div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'br', 'tr', 'li', 'blockquote'];

        if ($tag === 'ruby') {
            foreach ($node->childNodes as $child) {
                if ($child->nodeType === XML_ELEMENT_NODE && strtolower($child->nodeName ?? '') === 'rb') {
                    $parts[] = $child->textContent;
                    break;
                }
            }
            return;
        }

        if ($tag === 'script' || $tag === 'style') {
            return;
        }

        if ($tag === 'br') {
            $parts[] = "\n";
            return;
        }

        $isBlock = in_array($tag, $blockTags, true);

        if ($isBlock) {
            $parts[] = "\n";
        }

        foreach ($node->childNodes as $child) {
            $this->collectTextNodes($child, $parts);
        }

        if ($isBlock) {
            $parts[] = "\n";
        }
    }

    private function resolveUrl(string $baseUrl, string $href): string
    {
        if (preg_match('#^https?://#i', $href)) {
            return $href;
        }

        $base = parse_url($baseUrl);
        $scheme = $base['scheme'] ?? 'https';
        $host = $base['host'] ?? '';
        $path = $base['path'] ?? '/';

        $dir = preg_replace('#/[^/]*$#', '/', $path);

        if (str_starts_with($href, '/')) {
            return "{$scheme}://{$host}{$href}";
        }

        return "{$scheme}://{$host}{$dir}{$href}";
    }
}
