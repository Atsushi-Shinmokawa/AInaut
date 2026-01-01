<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookChunk extends Model
{
    use HasUuids;

    protected $fillable = [
        'book_id','book_document_id','chunk_index','content','char_length',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(BookDocument::class, 'book_document_id');
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
