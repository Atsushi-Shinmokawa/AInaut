<?php

namespace App\Models;

use App\Enums\BookMessageRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookMessage extends Model
{
    use HasUuids;

    protected $fillable = [
    'book_thread_id',
    'user_id',
    'book_id',
    'role',
    'content',
    'char_length',
  ];

    protected $casts = [
        'role' => BookMessageRole::class,
    ];

    public function thread(): BelongsTo
    {
        return $this->belongsTo(BookThread::class, 'book_thread_id');
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
