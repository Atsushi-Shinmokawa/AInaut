<?php

namespace App\Models\Domain;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'books';

    protected $fillable = [
        'title',
        'author',
        'isbn',
        'source_url',
        'tags',
        'cover_url',
        'user_id',
    ];

    protected $casts = [
        'tags' => 'array',
    ];
}
