<?php

namespace App\Services\Chat;

use App\Models\Book;
use App\Models\BookHighlight;
use App\Models\BookChunk;

class BookContextBuilder
{
  /**
   * RAG最小：本に紐づくデータを “使える範囲だけ” まとめる
   */
  public function build(Book $book, int $maxChars = 9000): string
  {
    // 1) highlights（新しい順でもOK。ここは好み）
    $highlights = BookHighlight::where('book_id', $book->id)
      ->orderByDesc('created_at')
      ->limit(200)
      ->get(['content']);

    $highlightText = $highlights
      ->pluck('content')
      ->map(fn ($c) => trim((string)$c))
      ->filter()
      ->map(fn ($c) => "• " . $c)
      ->implode("\n");

    // 2) notes：まだ無いなら空でOK（将来 ReadingLogs 等へ差し替え）
    $notesText = ""; // v1

    // 3) chunks：先頭から（or 重要度順は次フェーズ）
    $chunks = BookChunk::where('book_id', $book->id)
      ->orderBy('chunk_index')
      ->limit(80)
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
}
