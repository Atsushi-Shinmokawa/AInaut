<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class AiSummary extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'book_id',
        'user_id',      // null なら「汎用本要約」
        'model_name',
        'content',
        'context_type',
        'meta',         // もし追加するなら（migration側にmeta json追加したとき）
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
