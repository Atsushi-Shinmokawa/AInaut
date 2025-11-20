<?php

namespace App\Http\Controllers;

use App\Services\BookService;
use Illuminate\Http\Request;

class BookController extends Controller
{
    public function __construct(private BookService $service) {}

    public function index(BookListRequest $request)
    {
        return Inertia::render('Books/Index', [
            'books' => $this->service->list($request->validated())
        ]);
    }

    public function store(BookStoreRequest $request)
    {
        $this->service->store($request->validated());
        return back()->with('success', '登録しました');
    }
}
