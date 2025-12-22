<?php

namespace App\Services\Document;

use Illuminate\Support\Facades\Http;

class AozoraFetcher
{
    /**
     * 青空文庫URLから本文テキストを取得する。
     * - URLが .txt ならそのまま取る
     * - それ以外（作品ページ等）なら、HTMLから .txt リンクを探して取る（最小実装）
     */
    public function fetchText(string $url): array
    {
        $url = trim($url);

        $this->assertAllowedHost($url);

        if (preg_match('/\.txt(\?.*)?$/i', $url)) {
            $txt = $this->get($url);
            return ['text' => $txt, 'resolved_url' => $url];
        }

        // 作品ページなどのHTMLを取る
        $html = $this->get($url);

        $txtUrl = $this->extractTxtUrl($html, $url);
        if (!$txtUrl) {
            throw new \RuntimeException('青空文庫ページから txt のリンクが見つかりませんでした。txtファイルURLを直接貼ってください。');
        }

        $txt = $this->get($txtUrl);
        return ['text' => $txt, 'resolved_url' => $txtUrl];
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
            throw new \RuntimeException("取得に失敗しました（HTTP {$res->status()}）");
        }

        // 青空のtxtはShift_JISのことがあるので、HTTPヘッダと内容からざっくり変換
        $body = $res->body();
        $body = $this->convertToUtf8($body);

        return $body;
    }

    private function convertToUtf8(string $body): string
    {
        // 判定が難しいので「まずUTF-8として成立するか」をチェックし、
        // ダメならSJIS-winとして変換する最小実装
        if (mb_check_encoding($body, 'UTF-8')) {
            return $body;
        }
        $converted = @mb_convert_encoding($body, 'UTF-8', 'SJIS-win');
        return $converted ?: $body;
    }

    private function assertAllowedHost(string $url): void
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (!$host) throw new \InvalidArgumentException('URLが不正です。');

        $allowed = ['www.aozora.gr.jp', 'aozora.gr.jp'];
        if (!in_array($host, $allowed, true)) {
            throw new \InvalidArgumentException('青空文庫（aozora.gr.jp）のURLのみ許可しています。');
        }
    }

    private function extractTxtUrl(string $html, string $baseUrl): ?string
    {
        // DOMでa[href]から .txt を探す（最初の1件を採用）
        $dom = new \DOMDocument();
        @$dom->loadHTML($html);

        $links = $dom->getElementsByTagName('a');
        foreach ($links as $a) {
            /** @var \DOMElement $a */
            $href = $a->getAttribute('href');
            if (!$href) continue;
            if (!preg_match('/\.txt(\?.*)?$/i', $href)) continue;

            // 相対URLを絶対化
            return $this->resolveUrl($baseUrl, $href);
        }

        return null;
    }

    private function resolveUrl(string $baseUrl, string $href): string
    {
        if (preg_match('#^https?://#i', $href)) return $href;

        $base = parse_url($baseUrl);
        $scheme = $base['scheme'] ?? 'https';
        $host = $base['host'] ?? '';
        $path = $base['path'] ?? '/';

        // baseのディレクトリ
        $dir = preg_replace('#/[^/]*$#', '/', $path);

        if (str_starts_with($href, '/')) {
            return "{$scheme}://{$host}{$href}";
        }
        return "{$scheme}://{$host}{$dir}{$href}";
    }
}
