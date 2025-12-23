<?php

namespace App\Services\Chat;

use Illuminate\Support\Facades\Http;

class OpenAiChatClient
{
  public function chat(array $messages): string
  {
    $key = config('services.openai.key');
    $model = config('services.openai.model');
    $base = rtrim(config('services.openai.base_url'), '/');

    if (!$key) {
      throw new \RuntimeException('OPENAI_API_KEY が未設定です');
    }

    $res = Http::timeout(30)
      ->retry(2, 300)
      ->withToken($key)
      ->post($base . '/chat/completions', [
        'model' => $model,
        'messages' => $messages,
        'temperature' => 0.4,
      ]);

    if (!$res->successful()) {
      $msg = $res->json('error.message') ?? 'OpenAI API error';
      throw new \RuntimeException($msg);
    }

    $content = $res->json('choices.0.message.content');
    if (!is_string($content) || trim($content) === '') {
      throw new \RuntimeException('AIの応答が空でした');
    }

    return $content;
  }
}
