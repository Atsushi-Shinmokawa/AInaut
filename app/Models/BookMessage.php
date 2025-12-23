<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

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
}
