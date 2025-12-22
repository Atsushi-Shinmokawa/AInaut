<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookChunk;
use App\Models\BookDocument;
use App\Services\Document\AozoraFetcher;
use App\Services\Document\TextChunker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class BookDocumentController extends Controller
{
    public function uploadTxt(Request $request, Book $book, TextChunker $chunker)
    {
        $data = $request->validate([
            'txt' => ['required', 'file', 'mimes:txt', 'max:5120'], // 5MB
        ]);

        $file = $data['txt'];
        $raw = file_get_contents($file->getRealPath());
        if ($raw === false) {
            throw ValidationException::withMessages(['txt' => 'ファイルの読み取りに失敗しました。']);
        }

        // 文字コード（最小）
        $text = mb_check_encoding($raw, 'UTF-8')
            ? $raw
            : (mb_convert_encoding($raw, 'UTF-8', 'SJIS-win') ?: $raw);

        $path = $this->storeText($book, $text, 'upload_txt', null, $file->getClientOriginalName());

        $this->persistDocumentAndChunks($book, $text, $path, 'upload_txt', null, $file->getClientOriginalName(), $chunker);

        return redirect()
            ->route('books.show', ['book' => $book->id, 'tab' => 'document'])
            ->with('success', '本文を取り込みました。');
    }

    public function fetchAozora(Request $request, Book $book, AozoraFetcher $fetcher, TextChunker $chunker)
    {
        $data = $request->validate([
            'aozora_url' => ['required', 'string', 'url', 'max:2000'],
        ]);

        try {
            $result = $fetcher->fetchText($data['aozora_url']);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        $text = $result['text'];
        $resolvedUrl = $result['resolved_url'];

        $path = $this->storeText($book, $text, 'aozora_fetch', $resolvedUrl, null);

        $this->persistDocumentAndChunks($book, $text, $path, 'aozora_fetch', $resolvedUrl, null, $chunker);

        return redirect()
            ->route('books.show', ['book' => $book->id, 'tab' => 'document'])
            ->with('success', '青空文庫から本文を取り込みました。');
    }

    private function storeText(Book $book, string $text, string $source, ?string $sourceUrl, ?string $originalFilename): string
    {
        $userId = Auth::id();
        $dir = "book_documents/{$userId}/{$book->id}";
        $filename = now()->format('Ymd_His') . "_{$source}.txt";

        // localディスク（storage/app）に保存
        $path = "{$dir}/{$filename}";
        Storage::disk('local')->put($path, $text);

        return $path;
    }

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
            // 既存があるなら「最新版だけ残す」か「複数保持」か決められるが、
            // v1は簡単に「既存を削除して差し替え」でOK（迷いが減る）
            $existing = BookDocument::where('book_id', $book->id)->where('user_id', $userId)->first();
            if ($existing) {
                BookChunk::where('book_document_id', $existing->id)->delete();
                $existing->delete();
            }

            $doc = BookDocument::create([
                'user_id' => $userId,
                'book_id' => $book->id,
                'source' => $source,
                'source_url' => $sourceUrl,
                'original_filename' => $originalFilename,
                'storage_path' => $storagePath,
                'text_length' => mb_strlen($text),
            ]);

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
