<?php

namespace App\Http\Controllers;

use App\Models\BookHighlight;
use Illuminate\Http\Request;

class BookHighlightController extends Controller
{
    public function destroy(BookHighlight $highlight)
    {
        $highlight->delete();

        return back()->with('success', 'ハイライトを削除しました');
    }

    public function attach(BookHighlight $highlight, Request $request)
    {
        $request->validate([
            'book_id' => ['required', 'uuid'],
        ]);

        $highlight->update([
            'book_id' => $request->book_id,
        ]);

        return back()->with('success', 'ハイライトを本に紐付けました');
    }
}
