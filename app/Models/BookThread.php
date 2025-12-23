<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookThread extends Model
{
  use HasUuids;

  protected $fillable = ['user_id', 'book_id', 'title'];

  public function messages(): HasMany
  {
    return $this->hasMany(BookMessage::class, 'book_thread_id');
  }
}
