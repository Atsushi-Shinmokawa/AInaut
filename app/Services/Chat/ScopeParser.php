<?php

namespace App\Services\Chat;

/**
 * ユーザー発話から「本のどの範囲を参照するか」をルールベースで解釈する。
 */
class ScopeParser
{
    /**
     * 発話テキストを解析して ChatScope を返す。
     * 複数キーワードがある場合は、後半系 > 前半系 > 全体 > デフォルト の優先度とする。
     */
    public function parse(string $query): ChatScope
    {
        $query = mb_convert_kana($query, 'n', 'UTF-8');
        $query = trim($query);
        if ($query === '') {
            return new ChatScope(ChatScope::TYPE_DEFAULT);
        }

        // 後半・終盤・ラスト系（優先）
        if ($this->matchesSecondHalf($query)) {
            return new ChatScope(ChatScope::TYPE_SECOND_HALF);
        }

        // 前半・冒頭・最初系
        if ($this->matchesFirstHalf($query)) {
            return new ChatScope(ChatScope::TYPE_FIRST_HALF);
        }

        if ($this->matchesOpening($query)) {
            return new ChatScope(ChatScope::TYPE_OPENING);
        }

        if ($this->matchesEnding($query)) {
            return new ChatScope(ChatScope::TYPE_ENDING);
        }

        // 全体
        if ($this->matchesWhole($query)) {
            return new ChatScope(ChatScope::TYPE_WHOLE);
        }

        return new ChatScope(ChatScope::TYPE_DEFAULT);
    }

    private function matchesSecondHalf(string $query): bool
    {
        $patterns = [
            '/後半/u',
            '/終盤/u',
            '/ラスト/u',
            '/最後/u',
            '/終わり/u',
            '/結末/u',
            '/後半の/u',
            '/終盤の/u',
        ];
        foreach ($patterns as $p) {
            if (preg_match($p, $query)) {
                return true;
            }
        }
        return false;
    }

    private function matchesFirstHalf(string $query): bool
    {
        $patterns = [
            '/前半/u',
            '/最初のほう/u',
            '/初めのほう/u',
            '/前半の/u',
        ];
        foreach ($patterns as $p) {
            if (preg_match($p, $query)) {
                return true;
            }
        }
        return false;
    }

    private function matchesOpening(string $query): bool
    {
        $patterns = [
            '/冒頭/u',
            '/最初/u',
            '/始まり/u',
            '/オープニング/u',
        ];
        foreach ($patterns as $p) {
            if (preg_match($p, $query)) {
                return true;
            }
        }
        return false;
    }

    private function matchesEnding(string $query): bool
    {
        $patterns = [
            '/エンディング/u',
            '/締め/u',
        ];
        foreach ($patterns as $p) {
            if (preg_match($p, $query)) {
                return true;
            }
        }
        return false;
    }

    private function matchesWhole(string $query): bool
    {
        $patterns = [
            '/全体/u',
            '/通して/u',
            '/全体で/u',
            '/本全体/u',
            '/一通り/u',
        ];
        foreach ($patterns as $p) {
            if (preg_match($p, $query)) {
                return true;
            }
        }
        return false;
    }
}
