<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class BookChunk extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'book_id',
        'book_document_id',
        'chunk_index',
        'content',
        'char_length',
    ];
}
