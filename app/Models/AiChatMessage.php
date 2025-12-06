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
        'chat_thread_id',
        'role',
        'content',
        'token_usage',
    ];

    public function thread(): BelongsTo
    {
        return $this->belongsTo(AiChatThread::class, 'chat_thread_id');
    }
}
