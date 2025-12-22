<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookDocument extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'book_id',
        'source',
        'source_url',
        'original_filename',
        'storage_path',
        'text_length',
    ];

    public function chunks(): HasMany
    {
        return $this->hasMany(BookChunk::class);
    }
}
