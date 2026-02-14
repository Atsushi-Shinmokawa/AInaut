<?php

namespace App\Services\Chat;

use App\Models\Book;
use App\Models\BookHighlight;
use App\Models\BookChunk;
use Illuminate\Support\Collection;

class BookContextBuilder
{
  /**
   * RAG最小：本に紐づくデータを “使える範囲だけ” まとめる
   */
  public function build(Book $book, int $maxChars = 9000): string
  {
    $highlightText = $this->getHighlightText($book);
    $notesText = ""; // v1

    // 3) chunks：先頭から。maxChars に応じて取得数を変える（要約は10万文字など多めに渡す）
    $chunkLimit = $this->resolveChunkLimit($maxChars);
    $chunks = BookChunk::where('book_id', $book->id)
      ->orderBy('chunk_index')
      ->limit($chunkLimit)
      ->get(['chunk_index', 'content']);

    $chunkText = $chunks
      ->map(fn ($c) => "【chunk {$c->chunk_index}】\n" . trim((string)$c->content))
      ->implode("\n\n");

    $full = $this->format($book->title, $highlightText, $notesText, $chunkText);

    // 文字数制限（ざっくり。トークンじゃなくcharでv1）
    if (mb_strlen($full) <= $maxChars) return $full;

    // 超えたら chunks を削る→ highlights を削る の順で削減（実務で安定）
    $chunkText = $this->truncateToFit($this->format($book->title, $highlightText, $notesText, $chunkText), $maxChars, 'CHUNKS');
    if (mb_strlen($chunkText) <= $maxChars) return $chunkText;

    $highlightText = $this->truncateString($highlightText, (int)($maxChars * 0.4));
    $full = $this->format($book->title, $highlightText, $notesText, "");
    return $this->truncateString($full, $maxChars);
  }

  /**
   * 事前に取得したチャンクを使って本の文脈を組み立てる（チャットの質問に応じた取得用）。
   * HIGHLIGHTS / NOTES は従来どおり取得し、CHUNKS だけ渡された $chunks を使用する。
   *
   * @param  Collection<int, BookChunk>  $chunks
   */
  public function buildFromChunks(Book $book, Collection $chunks, int $maxChars = 9000): string
  {
    $highlightText = $this->getHighlightText($book);
    $notesText = ""; // v1

    $chunkText = $chunks
      ->sortBy('chunk_index')
      ->map(fn ($c) => "【chunk {$c->chunk_index}】\n" . trim((string) $c->content))
      ->implode("\n\n");

    $full = $this->format($book->title, $highlightText, $notesText, $chunkText);

    if (mb_strlen($full) <= $maxChars) {
      return $full;
    }

    $chunkText = $this->truncateToFit(
      $this->format($book->title, $highlightText, $notesText, $chunkText),
      $maxChars,
      'CHUNKS'
    );
    if (mb_strlen($chunkText) <= $maxChars) {
      return $chunkText;
    }

    $highlightText = $this->truncateString($highlightText, (int) ($maxChars * 0.4));
    $full = $this->format($book->title, $highlightText, $notesText, "");

    return $this->truncateString($full, $maxChars);
  }

  private function getHighlightText(Book $book): string
  {
    $highlights = BookHighlight::where('book_id', $book->id)
      ->orderByDesc('created_at')
      ->limit(200)
      ->get(['content']);

    return $highlights
      ->pluck('content')
      ->map(fn ($c) => trim((string) $c))
      ->filter()
      ->map(fn ($c) => "• " . $c)
      ->implode("\n");
  }

  private function format(string $title, string $highlights, string $notes, string $chunks): string
  {
    return trim(
"【BOOK】
{$title}

【HIGHLIGHTS】
{$highlights}

【NOTES】
{$notes}

【CHUNKS】
{$chunks}
"
    );
  }

  private function truncateToFit(string $text, int $maxChars, string $sectionLabel): string
  {
    // v1：単純に末尾を切る（重要度を入れるのは次フェーズ）
    return $this->truncateString($text, $maxChars);
  }

  private function truncateString(string $text, int $maxChars): string
  {
    if (mb_strlen($text) <= $maxChars) return $text;
    return mb_substr($text, 0, $maxChars - 50) . "\n...\n(省略)";
  }

  /**
   * maxChars から取得するチャンク数の上限を決める。
   * チャットは 80、要約（10万文字）では約 125。最大 500 で打ち止め。
   */
  private function resolveChunkLimit(int $maxChars): int
  {
    $default = 80;
    $max = 500;
    if ($maxChars <= 9000) {
      return $default;
    }
    $need = (int) ceil($maxChars / 800);

    return min($max, max($default, $need));
  }
}
