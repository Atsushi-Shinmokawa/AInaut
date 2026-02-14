<?php

namespace App\Services\Chat;

use App\Models\Book;
use App\Models\BookChunk;
use Illuminate\Support\Collection;

/**
 * 本と範囲（ChatScope）に応じて、参照すべきチャンクを取得する。
 * MVP: ルールベースで chunk_index の範囲を決め、その範囲から取得する。
 */
class ChunkRetriever
{
    public function __construct() {}

    /**
     * @return Collection<int, BookChunk>
     */
    public function retrieve(
        Book $book,
        ChatScope $scope,
        int $topK = 80,
        int $withNeighbors = 1,
    ): Collection {
        $total = BookChunk::where('book_id', $book->id)->count();
        if ($total === 0) {
            return new Collection();
        }

        $chunkIndexes = $this->resolveChunkIndexes($scope, $total, $topK);
        if ($chunkIndexes === []) {
            return $this->defaultChunks($book, $total, $topK, $withNeighbors);
        }

        $chunks = BookChunk::where('book_id', $book->id)
            ->whereIn('chunk_index', $chunkIndexes)
            ->orderBy('chunk_index')
            ->get();

        if ($withNeighbors > 0) {
            $chunks = $this->addNeighbors($book, $chunks, $withNeighbors, $topK);
        }

        return $chunks->sortBy('chunk_index')->values();
    }

    /**
     * scope に応じて取得する chunk_index のリストを決める。
     * @return int[] 取得する chunk_index の配列（昇順想定で返す）
     */
    private function resolveChunkIndexes(ChatScope $scope, int $total, int $topK): array
    {
        if ($scope->isDefault()) {
            return [];
        }

        $oneBasedMax = $total;

        if ($scope->isWhole()) {
            // 全体: 先頭から topK 個（長い本では末尾は切れるが、まずは先頭優先で一貫）
            return range(1, min($topK, $oneBasedMax));
        }

        if ($scope->isSecondHalf() || $scope->isEnding()) {
            // 後半: 最後の 1/3 の範囲から、末尾側を topK 個
            $start = (int) max(1, ceil($oneBasedMax * 2 / 3));
            $indexes = range($start, $oneBasedMax);
            return array_slice($indexes, -$topK);
        }

        if ($scope->isFirstHalf()) {
            // 前半: 先頭 1/2 の範囲から先頭 topK 個
            $end = (int) min($oneBasedMax, floor($oneBasedMax / 2));
            $indexes = range(1, $end);
            return array_slice($indexes, 0, $topK);
        }

        if ($scope->isOpening()) {
            // 冒頭: 先頭 1/4 程度から topK 個
            $end = (int) min($oneBasedMax, max(1, floor($oneBasedMax / 4)));
            $indexes = range(1, $end);
            return array_slice($indexes, 0, $topK);
        }

        return [];
    }

    /**
     * デフォルト時: 先頭中心だが「偏り」を減らすため、先頭＋末尾を混ぜる。
     * 先頭 60 + 末尾 20 を max topK に収める。
     */
    private function defaultChunks(Book $book, int $total, int $topK, int $withNeighbors): Collection
    {
        $headCount = (int) min($topK - 10, ceil($topK * 0.75));
        $tailCount = min(20, $topK - $headCount, $total - $headCount);
        if ($tailCount < 1) {
            $tailCount = 0;
        }

        $headIndexes = range(1, min($headCount, $total));
        $tailStart = max(1, $total - $tailCount + 1);
        $tailIndexes = $tailStart <= $total ? range($tailStart, $total) : [];

        $indexes = array_values(array_unique(array_merge($headIndexes, $tailIndexes)));
        sort($indexes);

        $chunks = BookChunk::where('book_id', $book->id)
            ->whereIn('chunk_index', $indexes)
            ->orderBy('chunk_index')
            ->get();

        if ($withNeighbors > 0) {
            $chunks = $this->addNeighbors($book, $chunks, $withNeighbors, $topK);
        }

        return $chunks->sortBy('chunk_index')->values();
    }

    /**
     * 取得したチャンクの前後に隣接チャンクを足し、文脈を補完する。
     */
    private function addNeighbors(Book $book, Collection $chunks, int $withNeighbors, int $topK): Collection
    {
        if ($chunks->isEmpty()) {
            return $chunks;
        }

        $indexes = $chunks->pluck('chunk_index')->all();
        $minIdx = min($indexes);
        $maxIdx = max($indexes);
        $total = BookChunk::where('book_id', $book->id)->count();

        $expandMin = max(1, $minIdx - $withNeighbors);
        $expandMax = min($total, $maxIdx + $withNeighbors);
        $expandedIndexes = range($expandMin, $expandMax);

        $all = BookChunk::where('book_id', $book->id)
            ->whereIn('chunk_index', $expandedIndexes)
            ->orderBy('chunk_index')
            ->get();

        if ($all->count() <= $topK) {
            return $all;
        }

        return $all->take($topK);
    }
}
