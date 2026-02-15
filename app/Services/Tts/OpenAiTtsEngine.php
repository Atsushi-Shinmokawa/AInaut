<?php

namespace App\Services\Tts;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiTtsEngine
{
    public function synthesize(string $text, string $model = 'tts-1', string $voice = 'alloy', float $speed = 1.0): string
    {
        $key = config('services.openai.key');
        $baseUrl = rtrim(config('services.openai.base_url', 'https://api.openai.com/v1'), '/');

        if (empty($key)) {
            throw new \InvalidArgumentException('OPENAI_API_KEY is not set.');
        }

        $response = Http::withToken($key)
            ->timeout(60)
            ->withBody(json_encode([
                'model' => $model,
                'input' => mb_substr($text, 0, 4096),
                'voice' => $voice,
                'speed' => $speed,
                'response_format' => 'mp3',
            ]), 'application/json')
            ->post($baseUrl . '/audio/speech');

        if (!$response->successful()) {
            Log::warning('OpenAI TTS API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException('TTS synthesis failed: HTTP ' . $response->status());
        }

        return $response->body();
    }
}
