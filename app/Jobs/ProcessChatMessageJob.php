<?php

namespace App\Jobs;

use App\Models\BookMessage;
use App\Services\Chat\BookChatService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

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
        $userMessage = BookMessage::find($this->userMessageId);

        if (!$userMessage || $userMessage->role !== \App\Enums\BookMessageRole::USER) {
            return;
        }

        $chatService->generateResponseForUserMessage($userMessage);
    }
}
