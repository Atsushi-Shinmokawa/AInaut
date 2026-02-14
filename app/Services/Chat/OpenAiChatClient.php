<?php

namespace App\Services\Chat;

use App\Exceptions\AppServiceExternalApiException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
        $temperature ??= (float) config('services.openai.temperature', 0.6);

        try {
            $res = $this->client()->post('/chat/completions', [
                'model' => $model,
                'messages' => $messages,
                'temperature' => $temperature,
            ]);
        } catch (\Throwable $e) {
            Log::error('OpenAI API request exception (connection/timeout etc.)', [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
            ]);
            throw new AppServiceExternalApiException(
                'OpenAI APIとの通信に失敗しました。（接続エラー: ' . $e->getMessage() . '）',
                null,
                'OpenAI',
                '/chat/completions',
                $e->getMessage()
            );
        }

        if (!$res->successful()) {
            $status = $res->status();
            $body = $res->body();
            Log::warning('OpenAI API error', [
                'status' => $status,
                'body' => $body,
                'endpoint' => '/chat/completions',
            ]);
            throw new AppServiceExternalApiException(
                "OpenAI APIとの通信に失敗しました。（HTTP {$status}）",
                $status,
                'OpenAI',
                '/chat/completions',
                $body
            );
        }

        return (string) data_get($res->json(), 'choices.0.message.content', '');
    }
}
