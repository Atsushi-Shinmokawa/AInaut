<?php

namespace App\Jobs;

use App\Models\BookMessage;
use App\Services\Chat\BookChatService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessChatMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** ジョブが実行できる最大秒数（OpenAI API 応答待ちを考慮） */
    public int $timeout = 120;

    /** リトライ前に待機する秒数（timeout より長くして二重実行を防ぐ） */
    public int $backoff = 90;

    /** 最大リトライ回数 */
    public int $tries = 3;

    public function __construct(
        private string $userMessageId
    ) {}

    public function handle(BookChatService $chatService): void
    {
        Log::info('ProcessChatMessageJob::handle start', ['user_message_id' => $this->userMessageId]);

        $userMessage = BookMessage::find($this->userMessageId);

        if (!$userMessage || $userMessage->role !== \App\Enums\BookMessageRole::USER) {
            Log::warning('ProcessChatMessageJob::handle skip', ['user_message_id' => $this->userMessageId, 'found' => (bool) $userMessage]);
            return;
        }

        $chatService->generateResponseForUserMessage($userMessage);

        Log::info('ProcessChatMessageJob::handle done', ['user_message_id' => $this->userMessageId]);
    }
}
