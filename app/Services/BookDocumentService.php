<?php

namespace App\Services;

use App\Models\Book;
use App\Models\BookChunk;
use App\Models\BookDocument;
use App\Services\Document\AozoraFetcher;
use App\Services\Document\TextChunker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class BookDocumentService
{
    public function __construct(
        private readonly AozoraFetcher $aozoraFetcher,
        private readonly TextChunker $textChunker,
    ) {}

    /**
     * テキストファイルをアップロードして保存
     */
    public function uploadTxt(Book $book, UploadedFile $file): void
    {
        // ファイル読み込み
        $raw = file_get_contents($file->getRealPath());
        if ($raw === false) {
            throw ValidationException::withMessages(['txt' => 'ファイルの読み取りに失敗しました。']);
        }

        // 文字コード変換
        $text = mb_check_encoding($raw, 'UTF-8')
            ? $raw
            : (mb_convert_encoding($raw, 'UTF-8', 'SJIS-win') ?: $raw);

        $originalFilename = $file->getClientOriginalName();
        $path = $this->storeText($book, $text, 'upload_txt', null, $originalFilename);
        $this->persistDocumentAndChunks($book, $text, $path, 'upload_txt', null, $originalFilename);
    }

    /**
     * 青空文庫からテキストを取得して保存
     */
    public function fetchAozora(Book $book, string $url): void
    {
        // 外部API呼び出し（ビジネスロジック）
        $result = $this->aozoraFetcher->fetchText($url);

        $path = $this->storeText($book, $result['text'], 'aozora_fetch', $result['resolved_url'], null);
        $this->persistDocumentAndChunks($book, $result['text'], $path, 'aozora_fetch', $result['resolved_url'], null);
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
    ): void {
        $userId = Auth::id();

        DB::transaction(function () use ($book, $text, $storagePath, $source, $sourceUrl, $originalFilename, $userId) {
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
            $chunks = $this->textChunker->chunk($text, 800, 1200);

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
