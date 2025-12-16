<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use App\Http\Controllers\BookController;
use App\Http\Controllers\ReadingLogController;
use App\Http\Controllers\ReadingNoteController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\HighlightImportController;

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

Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/books/search', [BookController::class, 'search'])
        ->name('books.search');

    Route::post('/books', [BookController::class, 'store'])
        ->name('books.store');

    // マイ本棚（Bookの一覧）
    Route::get('/my-books', [ReadingLogController::class, 'index'])
        ->name('reading-logs.index');

    // 読書ログ一覧
    Route::get('/reading-logs', [ReadingLogController::class, 'index'])
        ->name('reading-logs.index');

    Route::post('/reading-logs', [ReadingLogController::class, 'store'])
        ->name('reading-logs.store');

    Route::put('/reading-logs/{readingLog}', [ReadingLogController::class, 'update'])
        ->name('reading-logs.update');

    Route::delete('/reading-logs/{readingLog}', [ReadingLogController::class, 'destroy'])
        ->name('reading-logs.destroy');

        // 🔹 読書メモ 追加
    Route::post('/reading-logs/{readingLog}/notes', [ReadingNoteController::class, 'store'])
    ->name('reading-notes.store');

// 🔹 読書メモ 削除
Route::delete('/reading-logs/{readingLog}/notes/{readingNote}', [ReadingNoteController::class, 'destroy'])
    ->name('reading-notes.destroy');

    Route::get('/imports/kindle', [HighlightImportController::class, 'create'])->name('imports.kindle.create');
    Route::post('/imports/kindle/preview', [HighlightImportController::class, 'preview'])->name('imports.kindle.preview');
    Route::post('/imports/kindle/commit', [HighlightImportController::class, 'commit'])->name('imports.kindle.commit');

    Route::delete('/highlights/{highlight}', [BookHighlightController::class, 'destroy'])
    ->name('highlights.destroy');

Route::post('/highlights/{highlight}/attach', [BookHighlightController::class, 'attach'])
    ->name('highlights.attach');

});


require __DIR__.'/auth.php';
