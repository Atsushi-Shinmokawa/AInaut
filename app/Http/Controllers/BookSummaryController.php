<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Services\Ai\BookSummaryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class BookSummaryController extends Controller
{
    public function generate(Book $book, BookSummaryService $service): RedirectResponse
    {
        $userId = (string) Auth::id();

        // 本が自分のものか？など制約を入れたいならここにポリシー/チェックを追加
        // $this->authorize('view', $book);

        try {
            $service->generateAndSave($book, $userId);

            return back()->with('success', 'AI要約を生成しました。');
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'AI要約の生成に失敗しました。' . $e->getMessage());
        }
    }
}
