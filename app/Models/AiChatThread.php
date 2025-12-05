<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class AiChatThread extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'book_id', // どの本についてのチャットか（任意）
        'title',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AiChatMessage::class);
    }
}