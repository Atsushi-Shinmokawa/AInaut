<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Concerns\HasUuids; // 追加

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasUuids; // HasUuidsを追加

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // ▼ ここから下を追加 (Level 2-3: リレーション定義)

    /**
     * ユーザーの読書ログ
     */
    public function readingLogs(): HasMany
    {
        return $this->hasMany(ReadingLog::class);
    }

    /**
     * ユーザーのチャットスレッド
     */
    public function aiChatThreads(): HasMany
    {
        return $this->hasMany(AiChatThread::class);
    }
}