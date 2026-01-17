<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookChunk;
use App\Models\BookDocument;
use App\Services\BookDocumentService;
use App\Services\Document\AozoraFetcher;
use App\Services\Document\TextChunker;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BookDocumentController extends Controller
{
    public function __construct(
        private readonly BookDocumentService $bookDocumentService,
    ) {}

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

        // 文字コード変換
        $text = mb_check_encoding($raw, 'UTF-8')
            ? $raw
            : (mb_convert_encoding($raw, 'UTF-8', 'SJIS-win') ?: $raw);

        $this->bookDocumentService->uploadTxt(
            $book,
            $text,
            $file->getClientOriginalName(),
            $chunker
        );

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

        $this->bookDocumentService->fetchAozora(
            $book,
            $result['text'],
            $result['resolved_url'],
            $chunker
        );

        return redirect()
            ->route('books.show', ['book' => $book->id, 'tab' => 'document'])
            ->with('success', '青空文庫から本文を取り込みました。');
    }

}
