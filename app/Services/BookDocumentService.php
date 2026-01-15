<?php

namespace App\Services;

use App\Models\Book;
use App\Models\BookChunk;
use App\Models\BookDocument;
use App\Services\Document\TextChunker;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BookDocumentService
{
    /**
     * テキストファイルをアップロードして保存
     */
    public function uploadTxt(Book $book, string $text, string $originalFilename, TextChunker $chunker): void
    {
        $path = $this->storeText($book, $text, 'upload_txt', null, $originalFilename);
        $this->persistDocumentAndChunks($book, $text, $path, 'upload_txt', null, $originalFilename, $chunker);
    }

    /**
     * 青空文庫からテキストを取得して保存
     */
    public function fetchAozora(Book $book, string $text, string $resolvedUrl, TextChunker $chunker): void
    {
        $path = $this->storeText($book, $text, 'aozora_fetch', $resolvedUrl, null);
        $this->persistDocumentAndChunks($book, $text, $path, 'aozora_fetch', $resolvedUrl, null, $chunker);
    }

    /**
     * テキストをストレージに保存
     */
    private function storeText(Book $book, string $text, string $source, ?string $sourceUrl, ?string $originalFilename): string
    {
        $userId = Auth::id();
        $dir = "book_documents/{$userId}/{$book->id}";
        $filename = now()->format('Ymd_His') . "_{$source}.txt";

        $path = "{$dir}/{$filename}";
        Storage::disk('local')->put($path, $text);

        return $path;
    }

    /**
     * ドキュメントとチャンクをデータベースに保存
     */
    private function persistDocumentAndChunks(
        Book $book,
        string $text,
        string $storagePath,
        string $source,
        ?string $sourceUrl,
        ?string $originalFilename,
        TextChunker $chunker
    ): void {
        $userId = Auth::id();

        DB::transaction(function () use ($book, $text, $storagePath, $source, $sourceUrl, $originalFilename, $chunker, $userId) {
            // 既存があるなら削除
            $existing = BookDocument::where('book_id', $book->id)
                ->where('user_id', $userId)
                ->first();

            if ($existing) {
                BookChunk::where('book_document_id', $existing->id)->delete();
                $existing->delete();
            }

            // ドキュメント作成
            $doc = BookDocument::create([
                'user_id' => $userId,
                'book_id' => $book->id,
                'source' => $source,
                'source_url' => $sourceUrl,
                'original_filename' => $originalFilename,
                'storage_path' => $storagePath,
                'text_length' => mb_strlen($text),
            ]);

            // チャンク作成
            $chunks = $chunker->chunk($text, 800, 1200);

            foreach ($chunks as $i => $content) {
                BookChunk::create([
                    'user_id' => $userId,
                    'book_id' => $book->id,
                    'book_document_id' => $doc->id,
                    'chunk_index' => $i + 1,
                    'content' => $content,
                    'char_length' => mb_strlen($content),
                ]);
            }
        });
    }
}
