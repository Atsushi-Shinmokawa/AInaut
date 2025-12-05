<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne; // ポリモーフィック用
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ReadingLog extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'book_id',
        'status', // unread, reading, completed
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at' => 'date',
        'completed_at' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function readingNotes(): HasMany
    {
        return $this->hasMany(ReadingNote::class);
    }

    public function aiSummary(): HasOne
    {
        return $this->hasOne(AiSummary::class);
    }

    // Level 3: AIジョブ（要約生成タスクなど）とのポリモーフィック連携
    public function aiJob(): MorphOne
    {
        return $this->morphOne(AiJob::class, 'target');
    }
}