<?php

declare(strict_types=1);

namespace App\DTO;

use Carbon\Carbon;

/**
 * 書籍情報をやり取りするための統一規格クラス
 * 外部API(GoogleBooks/OpenBD)のレスポンス差異をここで吸収する
 */
readonly class BookData
{
    public function __construct(
        public string $title,
        public ?string $subTitle,
        public array $authors,      // string[]
        public ?string $isbn,       // ISBN13推奨
        public ?string $publisher,
        public ?Carbon $publishedAt,
        public ?string $description,
        public ?string $coverUrl,
        public array $rawResponse,  // デバッグや詳細保存用の生データ
    ) {}

    /**
     * 配列データ（APIレスポンス等）からインスタンスを生成する静的メソッドの例
     * ※後ほどServiceクラスで具体的にAPIに合わせて実装しますが、
     *   ここでは汎用的なコンストラクタのみ定義しておきます。
     */
}