<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Services\Ai\BookSummaryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Client\RequestException;

class BookSummaryController extends Controller
{
    public function generate(Book $book, BookSummaryService $service): RedirectResponse
    {
        $userId = (string) Auth::id();

        try {
            $service->generateAndSave($book, $userId);

            return back()->with('success', 'AI要約を生成しました。');
        } catch (RequestException $e) {
            report($e);

            $status = $e->response?->status();

            if ($status === 429) {
                return back()->with('error', 'AIが混雑しています。少し時間をおいて再度お試しください。');
            }
            if ($status === 401 || $status === 403) {
                return back()->with('error', 'AI機能の設定に問題があります。管理者に連絡してください。');
            }

            return back()->with('error', 'AI要約の生成に失敗しました。時間をおいて再度お試しください。');
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', '予期しないエラーが発生しました。時間をおいて再度お試しください。');
        }
    }
}
