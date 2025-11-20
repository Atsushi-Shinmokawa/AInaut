<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Domain\Book;
use App\Repositories\BookRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
class BookService
{
    public function __construct(private BookRepository $repo) {}

    public function list(array $params)
    {
        return $this->repo->paginate($params);
    }

    public function store(array $data)
    {
        return $this->repo->store($data);
    }
}
