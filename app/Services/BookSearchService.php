<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\BookData;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\RequestException;

class BookSearchService
{
    // Google Books API エンドポイント
    private const GOOGLE_BOOKS_API_URL = 'https://www.googleapis.com/books/v1/volumes';

    /**
     * ISBNで書籍を検索し、BookData DTOを返す
     * 
     * @param string $isbn
     * @return BookData|null 見つからない場合はnull
     */
    public function searchByIsbn(string $isbn): ?BookData
    {
        // 念のためここでも normalize しておいても良い
    $isbn = preg_replace('/[^0-9Xx]/', '', $isbn);
        try {
            // Level 3: タイムアウト設定とリトライ処理を入れる
            $response = Http::timeout(5)
                ->retry(3, 100) // 100ms間隔で3回リトライ
                ->get(self::GOOGLE_BOOKS_API_URL, [
                    'q' => "isbn:{$isbn}",
                ]);

            if ($response->failed()) {
                Log::warning("Google Books API Error: " . $response->status());
                return null;
            }


            $data = $response->json();

            // dd($data);
            if (($data['totalItems'] ?? 0) === 0) {
                return null;
            }

            // 最初の1件を取得
            $item = $data['items'][0];
            $volumeInfo = $item['volumeInfo'];

            // DTOに変換して返す（データの正規化）
            return new BookData(
                title: $volumeInfo['title'] ?? '不明なタイトル',
                subTitle: $volumeInfo['subtitle'] ?? null,
                authors: $volumeInfo['authors'] ?? ['不明な著者'],
                isbn: $isbn,
                publisher: $volumeInfo['publisher'] ?? null,
                publishedAt: isset($volumeInfo['publishedDate']) 
                    ? Carbon::parse($volumeInfo['publishedDate']) 
                    : null,
                description: $volumeInfo['description'] ?? null,
                coverUrl: $volumeInfo['imageLinks']['thumbnail'] ?? null,
                rawResponse: $item // 後でmeta_dataテーブルに入れるために生データも保持
            );

        } catch (RequestException $e) {
            // ネットワークエラー等の例外処理
            Log::error("Book Search Exception: " . $e->getMessage());
            return null;
        } catch (\Exception $e) {
            Log::error("Book Search Unexpected Error: " . $e->getMessage());
            return null;
        }
    }
}