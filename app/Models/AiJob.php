<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class AiJob extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'target_id',
        'target_type',
        'job_type', // summary, chat_response, etc.
        'status',   // pending, processing, completed, failed
        'payload',  // AIへの指示内容などをJSONで保存
        'result',   // 結果を一時保存する場合
        'error_message',
    ];

    // Level 3: JSONカラムを自動的に配列として扱う設定
    protected $casts = [
        'payload' => 'array',
        'result' => 'array',
    ];

    /**
     * 親モデル（ReadingLogなど）を取得するメソッド
     */
    public function target(): MorphTo
    {
        return $this->morphTo();
    }
}