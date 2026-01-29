<?php

namespace App\Exceptions;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 権限エラー用の例外
 * 
 * ユーザーに操作権限がない場合に使用
 */
class AppServiceForbiddenException extends AppServiceException
{
    protected int $statusCode = 403;
    protected string $logLevel = 'warning'; // 403エラーはwarningレベル（権限不足は通常の業務フロー）

    public function __construct(string $message = 'この操作を実行する権限がありません。')
    {
        parent::__construct($message);
    }

    /**
     * 403エラーをHTTPレスポンスに変換
     */
    public function toResponse(Request $request): Response
    {
        // ログに記録
        if ($this->shouldReport()) {
            $this->report();
        }

        abort(403, $this->getUserMessage());
    }
}
