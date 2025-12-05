<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class AiChatMessage extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'ai_chat_thread_id',
        'role', // user, assistant, system
        'content',
        'token_usage',
    ];

    public function thread(): BelongsTo
    {
        // 外部キー名が 'ai_chat_thread_id' なので第2引数で指定が必要な場合があるが、
        // Laravelの命名規則通りなら自動検知される。念の為明示的に書くのもあり。
        return $this->belongsTo(AiChatThread::class, 'ai_chat_thread_id');
    }
}