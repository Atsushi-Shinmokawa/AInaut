<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Domain\Book;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BookRepository
{
    public function paginateWithSearch(?string $word, int $perPage = 20): LengthAwarePaginator
    {
        $query = Book::query()->where('user_id', auth()->id());

        if ($word !== null && $word !== '') {
            $like = "%{$word}%";
            $query->where(function ($q) use ($like) {
                $q->where('title', 'like', $like)
                  ->orWhere('author', 'like', $like);
            });
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    public function findById(string $id): Book
    {
        return Book::where('user_id', auth()->id())->findOrFail($id);
    }

    public function store(array $data): Book
    {
        $data['user_id'] = auth()->id();

        return Book::create($data);
    }

    public function update(Book $book, array $data): Book
    {
        $book->fill($data)->save();

        return $book;
    }

    public function delete(Book $book): void
    {
        $book->delete();
    }
}
