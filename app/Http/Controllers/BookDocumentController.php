<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookDocumentFetchAozoraRequest;
use App\Http\Requests\BookDocumentUploadTxtRequest;
use App\Models\Book;
use App\Services\BookDocumentService;
use Illuminate\Http\RedirectResponse;

class BookDocumentController extends Controller
{
    public function __construct(
        private readonly BookDocumentService $bookDocumentService,
    ) {}

    public function uploadTxt(BookDocumentUploadTxtRequest $request, Book $book): RedirectResponse
    {
        $this->bookDocumentService->uploadTxt($book, $request->validated('txt'));

        return redirect()
            ->route('books.show', ['book' => $book->id, 'tab' => 'document'])
            ->with('success', '本文を取り込みました。');
    }

    public function fetchAozora(BookDocumentFetchAozoraRequest $request, Book $book): RedirectResponse
    {
        try {
            $this->bookDocumentService->fetchAozora($book, $request->validated('aozora_url'));
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('books.show', ['book' => $book->id, 'tab' => 'document'])
            ->with('success', '青空文庫から本文を取り込みました。');
    }

}
