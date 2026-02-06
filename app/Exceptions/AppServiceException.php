<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * サービス層の基底例外クラス
 * 
 * すべてのサービス層の例外はこのクラスを継承する
 * 
 * LaravelのRenderableインターフェースを実装し、
 * 例外自身がレスポンスを生成できるようにする
 */
abstract class AppServiceException extends Exception implements Responsable
{
    /**
     * HTTPステータスコード
     */
    protected int $statusCode = 500;

    /**
     * ユーザー向けエラーメッセージ
     */
    protected ?string $userMessage = null;

    /**
     * ログに記録するかどうか
     */
    protected bool $shouldReport = true;

    /**
     * ログレベル（debug, info, warning, error, critical）
     */
    protected string $logLevel = 'error';

    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * HTTPステータスコードを取得
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * ユーザー向けエラーメッセージを取得
     */
    public function getUserMessage(): ?string
    {
        return $this->userMessage ?? $this->getMessage();
    }

    /**
     * ユーザー向けエラーメッセージを設定
     */
    public function setUserMessage(string $message): self
    {
        $this->userMessage = $message;
        return $this;
    }

    /**
     * ログに記録するかどうか
     */
    public function shouldReport(): bool
    {
        return $this->shouldReport;
    }

    /**
     * ログレベルを取得
     */
    public function getLogLevel(): string
    {
        return $this->logLevel;
    }

    /**
     * 例外のコンテキスト情報を取得（ログ用）
     */
    public function context(): array
    {
        return [
            'status_code' => $this->statusCode,
            'user_message' => $this->getUserMessage(),
        ];
    }

    /**
     * 例外をHTTPレスポンスに変換（LaravelのResponsableインターフェース）
     * 
     * このメソッドにより、例外をthrowするだけで自動的に適切なレスポンスが生成される
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request): Response
    {
        // ログに記録
        if ($this->shouldReport()) {
            $this->report();
        }

        // 例外の種類に応じて適切なレスポンスを返す
        return $this->render($request);
    }

    /**
     * 例外をログに記録
     */
    protected function report(): void
    {
        $context = $this->context();
        
        // 前の例外がある場合はスタックトレースを含める
        if ($this->getPrevious()) {
            $context['previous'] = [
                'message' => $this->getPrevious()->getMessage(),
                'file' => $this->getPrevious()->getFile(),
                'line' => $this->getPrevious()->getLine(),
            ];
        }

        Log::{$this->logLevel}($this->getMessage(), $context);
    }

    /**
     * 例外をHTTPレスポンスに変換
     * 
     * Laravelの例外ハンドラから直接呼び出される可能性があるため、publicにする
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function render($request): Response
    {
        // ログに記録
        if ($this->shouldReport()) {
            $this->report();
        }

        // デフォルトの実装：リダイレクトでエラーメッセージを返す
        return back()->with('error', $this->getUserMessage());
    }
}
