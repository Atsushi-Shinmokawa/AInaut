<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Book extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'author_id',
        'title',
        'isbn',
        'publisher',         // 追加
        'source_url',        // 追加
        'cover_url',
        'tags',              // 追加
        'raw_api_response',  // 追加
        'published_at',
    ];

    // Level 3: 日付として扱いたいカラムを自動変換
    protected $casts = [
        'published_at' => 'date',
        'raw_api_response' => 'array',
        'tags'             => 'array',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    public function readingLogs(): HasMany
    {
        return $this->hasMany(ReadingLog::class);
    }
}