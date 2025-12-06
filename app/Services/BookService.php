<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\BookData;
use App\Models\Author;
use App\Models\Book;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookService
{
    /**
     * BookData DTOを受け取り、著者と書籍をDBに保存する
     * 
     * @param BookData $bookData
     * @return Book
     */
    public function persist(BookData $bookData): Book
    {
        // Level 3: 複数のテーブル更新があるため、必ずトランザクションを貼る
        return DB::transaction(function () use ($bookData) {
            
            // 1. 著者の処理
            // DTOのauthors配列の先頭をメイン著者として扱う（ER図が1対多の構造のため）
            // ※著者が空の場合は 'Unknown' などを入れる処理を入れても良いが今回はそのまま
            $authorName = $bookData->authors[0] ?? '不明な著者';

            // firstOrCreate: 既存の著者がいれば取得、なければ作成
            $author = Author::firstOrCreate(
                ['name' => $authorName]
            );

            // 2. 書籍の処理
            // ISBNで重複チェック。既に登録済みならそのインスタンスを返して終了（二重登録防止）
            $existingBook = Book::where('isbn', $bookData->isbn)->first();
            if ($existingBook) {
                return $existingBook;
            }

            // 3. 新規保存
            $book = Book::create([
                'author_id' => $author->id,
                'title' => $bookData->title,
                'isbn' => $bookData->isbn,
                'publisher' => $bookData->publisher,
                'cover_url' => $bookData->coverUrl,
                'raw_api_response' => $bookData->rawResponse, // JSONカラムに配列を入れるとLaravelが自動変換してくれる
                // 'published_at' => $bookData->publishedAt, // ※migrationにカラムがあれば追加
            ]);

            Log::info("Book persisted: {$book->title} (ID: {$book->id})");

            return $book;
        });
    }
}