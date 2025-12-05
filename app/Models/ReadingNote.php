<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ReadingNote extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'reading_log_id',
        'page_number',
        'content',
    ];

    public function readingLog(): BelongsTo
    {
        return $this->belongsTo(ReadingLog::class);
    }
}