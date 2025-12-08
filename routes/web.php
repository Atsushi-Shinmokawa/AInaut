<?php

use Illuminate\Foundation\Application;
use App\Http\Controllers\BookController;
use App\Http\Controllers\ReadingLogController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');


Route::middleware('auth')->group(function () {

    // 書籍検索画面の表示
    Route::get('/books/search', [BookController::class, 'search'])->middleware(['auth', 'verified'])->name('books.search');
    
    // 書籍の保存処理（Google Booksから選択して保存）
    Route::post('/books', [BookController::class, 'store'])->name('books.store');

    Route::get('/my-books', [BookController::class, 'index'])->name('books.index');

    Route::get('/my-books', [ReadingLogController::class, 'index'])
    ->name('reading-logs.index');

Route::post('/reading-logs', [ReadingLogController::class, 'store'])
    ->name('reading-logs.store');

Route::put('/reading-logs/{readingLog}', [ReadingLogController::class, 'update'])
    ->name('reading-logs.update');

Route::delete('/reading-logs/{readingLog}', [ReadingLogController::class, 'destroy'])
    ->name('reading-logs.destroy');
});

require __DIR__.'/auth.php';
