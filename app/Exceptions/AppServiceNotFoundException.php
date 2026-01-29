<?php

namespace App\Exceptions;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * リソースが見つからない場合の例外
 * 
 * データベースからリソースが見つからない場合に使用
 */
class AppServiceNotFoundException extends AppServiceException
{
    protected int $statusCode = 404;
    protected string $logLevel = 'warning'; // 404エラーはwarningレベル（ユーザーの操作ミスの可能性）

    public function __construct(string $message = 'リソースが見つかりませんでした。')
    {
        parent::__construct($message);
    }

    /**
     * 404エラーをHTTPレスポンスに変換
     */
    public function toResponse(Request $request): Response
    {
        // ログに記録
        if ($this->shouldReport()) {
            $this->report();
        }

        abort(404, $this->getUserMessage());
    }
}
