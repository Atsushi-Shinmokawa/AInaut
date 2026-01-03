<?php

namespace App\Services\Chat;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class OpenAiChatClient
{
    private function client(): PendingRequest
    {
        return Http::withToken(config('services.openai.key'))
            ->baseUrl(config('services.openai.base_url', 'https://api.openai.com/v1'))
            ->timeout(60);
    }

    /**
     * OpenAI Chat Completions を叩いて、assistantの文字列だけ返す
     *
     * @param array $messages 例: [['role'=>'system','content'=>'...'], ...]
     */
    public function chat(array $messages, ?string $model = null, ?float $temperature = null): string
    {
        $model ??= config('services.openai.model', 'gpt-4o-mini');
        $temperature ??= (float) config('services.openai.temperature', 0.4);

        $res = $this->client()->post('/chat/completions', [
            'model' => $model,
            'messages' => $messages,
            'temperature' => $temperature,
        ]);

        if (!$res->successful()) {
            throw new \RuntimeException('OpenAI API error: ' . $res->body());
        }

        return (string) data_get($res->json(), 'choices.0.message.content', '');
    }
}
