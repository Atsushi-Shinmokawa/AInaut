<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class BookHighlight extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id','book_id','source','title_raw','location','page','highlighted_at','content','content_hash',
    ];

    protected $casts = [
        'highlighted_at' => 'datetime',
    ];
}

