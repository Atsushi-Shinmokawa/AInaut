<?php

namespace App\Exceptions;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * 外部APIエラー用の例外
 * 
 * 外部API呼び出しに失敗した場合に使用
 * 
 * どのAPIで、どんなステータスが返ってきたかを保持し、
 * ログで自社のミスなのか、相手側のミスなのかを即座に判別できるようにする
 */
class AppServiceExternalApiException extends AppServiceException
{
    protected int $statusCode = 502;

    /**
     * 外部APIのHTTPステータスコード
     */
    protected ?int $apiStatusCode = null;

    /**
     * 外部APIの名前（例: 'OpenAI', 'Google Books API'）
     */
    protected ?string $apiName = null;

    /**
     * 外部APIのエンドポイントURL
     */
    protected ?string $apiEndpoint = null;

    /**
     * 外部APIのレスポンスボディ（デバッグ用、機密情報は含めない）
     */
    protected ?string $apiResponseBody = null;

    public function __construct(
        string $message = '外部APIとの通信に失敗しました。',
        ?int $apiStatusCode = null,
        ?string $apiName = null,
        ?string $apiEndpoint = null,
        ?string $apiResponseBody = null
    ) {
        parent::__construct($message);
        $this->apiStatusCode = $apiStatusCode;
        $this->apiName = $apiName;
        $this->apiEndpoint = $apiEndpoint;
        $this->apiResponseBody = $apiResponseBody;
    }

    /**
     * 外部APIのHTTPステータスコードを取得
     */
    public function getApiStatusCode(): ?int
    {
        return $this->apiStatusCode;
    }

    /**
     * 外部APIの名前を取得
     */
    public function getApiName(): ?string
    {
        return $this->apiName;
    }

    /**
     * 外部APIのエンドポイントURLを取得
     */
    public function getApiEndpoint(): ?string
    {
        return $this->apiEndpoint;
    }

    /**
     * 外部APIのレスポンスボディを取得
     */
    public function getApiResponseBody(): ?string
    {
        return $this->apiResponseBody;
    }

    /**
     * レート制限エラーかどうか
     */
    public function isRateLimit(): bool
    {
        if ($this->apiStatusCode !== 429) {
            return false;
        }

        // 429エラーでも、クォータ不足の場合はレート制限ではない
        return !$this->isQuotaExceeded();
    }

    /**
     * クォータ不足エラーかどうか
     */
    public function isQuotaExceeded(): bool
    {
        if ($this->apiStatusCode !== 429) {
            return false;
        }

        // レスポンスボディをパースして、insufficient_quotaエラーを検出
        if ($this->apiResponseBody) {
            $decoded = json_decode($this->apiResponseBody, true);
            if (isset($decoded['error']['code']) && $decoded['error']['code'] === 'insufficient_quota') {
                return true;
            }
            if (isset($decoded['error']['type']) && $decoded['error']['type'] === 'insufficient_quota') {
                return true;
            }
        }

        return false;
    }

    /**
     * 認証エラーかどうか
     */
    public function isAuthError(): bool
    {
        return in_array($this->apiStatusCode, [401, 403], true);
    }

    /**
     * 例外のコンテキスト情報を取得（ログ用）
     */
    public function context(): array
    {
        $context = parent::context();
        
        $context['external_api'] = [
            'name' => $this->apiName,
            'endpoint' => $this->apiEndpoint,
            'status_code' => $this->apiStatusCode,
            'response_body' => $this->apiResponseBody,
        ];

        return $context;
    }

    /**
     * 外部API例外をHTTPレスポンスに変換
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function toResponse($request): RedirectResponse
    {
        // ログに記録
        if ($this->shouldReport()) {
            $this->report();
        }

        // クォータ不足エラーの場合
        if ($this->isQuotaExceeded()) {
            if ($this->apiName === 'OpenAI') {
                return back()->with('error', 'OpenAI APIの利用可能なクレジットが不足しています。アカウント設定でクレジットを追加してください。');
            }
            return back()->with('error', 'APIの利用可能なクレジットが不足しています。アカウント設定を確認してください。');
        }

        // レート制限エラーの場合
        if ($this->isRateLimit()) {
            // Google Books APIの場合は専用メッセージ
            if ($this->apiName === 'Google Books API') {
                return back()->with('error', '書籍検索の利用制限に達しました。しばらく時間をおいてから再度お試しください。');
            }
            return back()->with('error', 'AIが混雑しています。少し時間をおいて再度お試しください。');
        }

        // 認証エラーの場合
        if ($this->isAuthError()) {
            return back()->with('error', 'AI機能の設定に問題があります。管理者に連絡してください。');
        }

        return back()->with('error', 'AIとの通信に失敗しました。時間をおいて再度お試しください。');
    }
}
