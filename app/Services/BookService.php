<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\BookData;
use App\Models\Author;
use App\Models\Book;
use Illuminate\Support\Str;

class BookService
{
    /**
     * 外部APIで取得した BookData をDBに永続化して Book を返す
     * - authorは “最初の著者名” を採用（v1）
     * - 同一ISBNが既にあれば update して返す（v1の安全策）
     */
    public function persist(BookData $data): Book
    {
        $authorName = $data->authors[0] ?? 'Unknown';

        $author = Author::firstOrCreate(
            ['name' => $authorName],
            ['memo' => null],
        );

        // ISBNがあれば同一判定をしやすいので活用（なければ新規）
        $book = null;
        if (!empty($data->isbn)) {
            $book = Book::where('isbn', $data->isbn)->first();
        }

        $payload = [
            'author_id' => $author->id,
            'title' => $data->title,
            'subtitle' => $data->subTitle,
            'isbn' => $data->isbn,
            'publisher' => $data->publisher,
            'published_at' => $data->publishedAt,
            'description' => $data->description,
            'cover_url' => $data->coverUrl,
            'raw_api_response' => $data->rawResponse,
        ];

        if ($book) {
            $book->fill($payload)->save();
            return $book;
        }

        return Book::create($payload);
    }
}
