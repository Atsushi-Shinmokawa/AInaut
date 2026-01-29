<?php

namespace App\Exceptions;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

/**
 * バリデーションエラー用の例外
 * 
 * 入力値の検証に失敗した場合に使用
 */
class AppServiceValidationException extends AppServiceException
{
    protected int $statusCode = 422;
    protected string $logLevel = 'warning'; // バリデーションエラーはwarningレベル

    /**
     * バリデーションエラーメッセージの配列
     */
    protected array $errors = [];

    public function __construct(string $message = 'バリデーションエラーが発生しました。', array $errors = [])
    {
        parent::__construct($message);
        $this->errors = $errors;
    }

    /**
     * バリデーションエラーの配列を取得
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * バリデーションエラーをHTTPレスポンスに変換
     * 
     * ValidationExceptionをthrowすることで、Laravelの標準的なバリデーションエラー処理を行う
     */
    public function toResponse(Request $request): Response
    {
        // ログに記録
        if ($this->shouldReport()) {
            $this->report();
        }

        // LaravelのValidationExceptionに変換して、標準的なバリデーションエラー処理を行う
        throw ValidationException::withMessages(
            $this->errors ?: ['error' => [$this->getUserMessage()]]
        );
    }
}
