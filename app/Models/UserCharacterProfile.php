<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCharacterProfile extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'character',
        'nickname',
        'speech_style',
        'favorite_genres',
        'custom_note',
    ];

    protected $casts = [
        'favorite_genres' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function toPromptText(): string
    {
        $parts = [];

        if ($this->nickname) {
            $parts[] = "このユーザーは「{$this->nickname}」と呼ばれることを好みます。";
        }

        if ($this->speech_style) {
            $style = match ($this->speech_style) {
                'polite'  => '丁寧で落ち着いた口調',
                'logical' => '論理的で落ち着いた口調',
                default   => 'フレンドリーで親しみやすい口調',
            };
            $parts[] = "あなたは {$style} で話してください。";
        }

        if (!empty($this->favorite_genres)) {
            $genres = implode('、', $this->favorite_genres);
            $parts[] = "このユーザーは次のジャンルの本が好きです：{$genres}。";
        }

        if ($this->custom_note) {
            $parts[] = $this->custom_note;
        }

        return implode("\n", $parts);
    }
}

