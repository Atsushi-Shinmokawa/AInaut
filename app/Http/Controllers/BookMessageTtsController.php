<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Services\Tts\TtsService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * チャットメッセージ用 TTS：リクエスト検証と HTTP レスポンスのみ。
 * 合成ロジック・デフォルト値は TtsService に委譲。
 */
class BookMessageTtsController extends Controller
{
    public function __construct(
        private TtsService $ttsService
    ) {}

    public function __invoke(Request $request, Book $book): Response
    {
        $validated = $request->validate([
            'text' => ['required', 'string', 'max:4096'],
            'tts_model' => ['sometimes', 'nullable', 'string', Rule::in(['tts-1', 'tts-1-hd'])],
            'speed' => ['sometimes', 'numeric', 'min:0.25', 'max:4'],
        ]);

        try {
            $audio = $this->ttsService->synthesize($validated['text'], [
                'model' => $validated['tts_model'] ?? null,
                'speed' => isset($validated['speed']) ? (float) $validated['speed'] : null,
            ]);
        } catch (\Throwable $e) {
            Log::error('TTS synthesis failed', ['message' => $e->getMessage(), 'book_id' => $book->id]);
            return response('TTS failed', 500);
        }

        return response($audio, 200, [
            'Content-Type' => 'audio/mpeg',
            'Content-Disposition' => 'inline',
        ]);
    }
}
