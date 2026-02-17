<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Exceptions\AppServiceExternalApiException;
use App\Services\BookService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BookServiceSearchTest extends TestCase
{
    private const GOOGLE_BOOKS_API_URL = 'https://www.googleapis.com/books/v1/volumes';

    protected function setUp(): void
{
    parent::setUp();

    // テスト間のキャッシュ汚染を防ぐ
    Cache::flush();

    // fakeしていない外部通信を検知
    Http::preventStrayRequests();
}


    private function getBookService(): BookService
    {
        return app(BookService::class);
    }

    private function loadFixture(string $name): array
    {
        $path = base_path("tests/Fixtures/google_books/{$name}.json");
        $this->assertFileExists($path, "Fixture not found: {$path}");
        $json = file_get_contents($path);
        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded, "Invalid JSON in fixture: {$name}");
        return $decoded;
    }

    #[Test]
    public function empty_query_returns_has_searched_false_and_sends_no_http_request(): void
    {
        Http::fake();

        $service = $this->getBookService();
        $result = $service->search('');
        $resultTrimmed = $service->search('   ');

        $this->assertSame([], $result['books']);
        $this->assertSame(['q' => ''], $result['filters']);
        $this->assertFalse($result['hasSearched']);

        $this->assertSame([], $resultTrimmed['books']);
        $this->assertFalse($resultTrimmed['hasSearched']);

        Http::assertNothingSent();
    }

    #[Test]
    public function isbn_search_sends_correct_params_and_returns_one_book_from_fixture(): void
    {
        $fixture = $this->loadFixture('success_isbn');
        Http::fake([
            self::GOOGLE_BOOKS_API_URL . '*' => Http::response($fixture, 200),
        ]);

        $service = $this->getBookService();
        $result = $service->search('9784101000018');

        $this->assertTrue($result['hasSearched']);
        $this->assertSame(['q' => '9784101000018'], $result['filters']);
        $this->assertCount(1, $result['books']);
        $book = $result['books'][0];
        $this->assertSame('テスト書籍タイトル', $book['title']);
        $this->assertSame(['著者A', '著者B'], $book['authors']);
        $this->assertSame('9784101000018', $book['isbn']);
        $this->assertSame('https://example.com/cover.jpg', $book['thumbnail']);

        Http::assertSentCount(1);
        Http::assertSent(function ($request) {
    $url = $request->url();
    if (!str_starts_with($url, self::GOOGLE_BOOKS_API_URL)) {
        return false;
    }

    parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

    return ($query['q'] ?? null) === 'isbn:9784101000018'
        && ($query['printType'] ?? null) === 'books'
        && ($query['langRestrict'] ?? null) === 'ja';
});

    }

    #[Test]
    public function isbn_search_with_hyphens_normalizes_and_returns_one_book(): void
    {
        $fixture = $this->loadFixture('success_isbn');
        Http::fake([
            self::GOOGLE_BOOKS_API_URL . '*' => Http::response($fixture, 200),
        ]);

        $service = $this->getBookService();
        $result = $service->search('978-4-10-100001-8');

        $this->assertCount(1, $result['books']);
        $this->assertSame('テスト書籍タイトル', $result['books'][0]['title']);
        Http::assertSentCount(1);
    }

    #[Test]
    public function keyword_search_sends_correct_params_and_returns_books_from_fixture(): void
    {
        $fixture = $this->loadFixture('success_keyword');
        Http::fake([
            self::GOOGLE_BOOKS_API_URL . '*' => Http::response($fixture, 200),
        ]);

        $service = $this->getBookService();
        $result = $service->search('サンプルキーワード');

        $this->assertTrue($result['hasSearched']);
        $this->assertSame(['q' => 'サンプルキーワード'], $result['filters']);
        $this->assertCount(2, $result['books']);
        $this->assertSame('キーワード検索 本1', $result['books'][0]['title']);
        $this->assertSame(['著者1'], $result['books'][0]['authors']);
        $this->assertSame('9784111000001', $result['books'][0]['isbn']);
        $this->assertSame('キーワード検索 本2', $result['books'][1]['title']);
        $this->assertSame(['著者2a', '著者2b'], $result['books'][1]['authors']);

        Http::assertSentCount(1);
        Http::assertSent(function ($request) {
    $url = $request->url();
    if (!str_starts_with($url, self::GOOGLE_BOOKS_API_URL)) {
        return false;
    }

    parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

    return ($query['q'] ?? null) === 'サンプルキーワード'
        && (string)($query['maxResults'] ?? '') === '10'
        && ($query['printType'] ?? null) === 'books'
        && ($query['langRestrict'] ?? null) === 'ja';
});

    }

    #[Test]
    public function isbn_search_with_zero_results_returns_empty_books(): void
    {
        $fixture = $this->loadFixture('empty');
        Http::fake([
            self::GOOGLE_BOOKS_API_URL . '*' => Http::response($fixture, 200),
        ]);

        $service = $this->getBookService();
        $result = $service->search('9784101000999');

        $this->assertTrue($result['hasSearched']);
        $this->assertSame([], $result['books']);
        Http::assertSentCount(1);
    }

    #[Test]
public function rate_limit_429_returns_empty_books_for_isbn_search(): void
{
    $body = $this->loadFixture('error_429');

    Http::fake([
        self::GOOGLE_BOOKS_API_URL . '*' => Http::response($body, 429),
    ]);

    $service = $this->getBookService();
    $result = $service->search('9784101000777');

    $this->assertSame([], $result['books']);
    $this->assertSame(['q' => '9784101000777'], $result['filters']);
    $this->assertTrue($result['hasSearched']);

    Http::assertSentCount(1);
}


    #[Test]
    public function same_isbn_search_twice_hits_cache_and_sends_http_only_once(): void
    {
        $fixture = $this->loadFixture('success_isbn');
        Http::fake([
            self::GOOGLE_BOOKS_API_URL . '*' => Http::response($fixture, 200),
        ]);

        $service = $this->getBookService();
        $result1 = $service->search('9784101000018');
        $result2 = $service->search('9784101000018');

        $this->assertCount(1, $result1['books']);
        $this->assertCount(1, $result2['books']);
        $this->assertSame($result1['books'][0]['title'], $result2['books'][0]['title']);
        Http::assertSentCount(1);
    }

    #[Test]
    public function fixture_success_isbn_response_shape_maps_to_expected_book_structure(): void
    {
        $fixture = $this->loadFixture('success_isbn');
        $this->assertArrayHasKey('totalItems', $fixture);
        $this->assertArrayHasKey('items', $fixture);
        $this->assertSame(1, $fixture['totalItems']);
        $item = $fixture['items'][0];
        $this->assertArrayHasKey('volumeInfo', $item);
        $vi = $item['volumeInfo'];
        $this->assertArrayHasKey('title', $vi);
        $this->assertArrayHasKey('authors', $vi);
        $this->assertArrayHasKey('industryIdentifiers', $vi);
        $this->assertArrayHasKey('imageLinks', $vi);

        Http::fake([self::GOOGLE_BOOKS_API_URL . '*' => Http::response($fixture, 200)]);
        $service = $this->getBookService();
        $result = $service->search('9784101000018');
        $book = $result['books'][0];
        $this->assertArrayHasKey('title', $book);
        $this->assertArrayHasKey('authors', $book);
        $this->assertArrayHasKey('isbn', $book);
        $this->assertArrayHasKey('thumbnail', $book);
        $this->assertSame($vi['title'], $book['title']);
        $this->assertSame($vi['authors'], $book['authors']);
    }

    #[Test]
    public function fixture_success_keyword_response_shape_maps_to_multiple_books(): void
    {
        $fixture = $this->loadFixture('success_keyword');
        $this->assertGreaterThanOrEqual(1, $fixture['totalItems']);
        $this->assertCount(2, $fixture['items']);

        Http::fake([self::GOOGLE_BOOKS_API_URL . '*' => Http::response($fixture, 200)]);
        $service = $this->getBookService();
        $result = $service->search('キーワード');
        $this->assertCount(2, $result['books']);
        foreach ($result['books'] as $book) {
            $this->assertArrayHasKey('title', $book);
            $this->assertArrayHasKey('authors', $book);
            $this->assertArrayHasKey('isbn', $book);
            $this->assertArrayHasKey('thumbnail', $book);
        }
    }

    #[Test]
public function isbn10_search_retries_with_converted_isbn13_when_first_result_is_empty(): void
{
    $empty = $this->loadFixture('empty');
    $success = $this->loadFixture('success_isbn');

    Http::fakeSequence()
        ->push($empty, 200)   // 1回目: isbn10 で0件
        ->push($success, 200); // 2回目: isbn13 でヒット

    $service = $this->getBookService();
    $result = $service->search('4101000012'); // ISBN10

    $this->assertTrue($result['hasSearched']);
    $this->assertCount(1, $result['books']);
    $this->assertSame('9784101000018', $result['books'][0]['isbn']);

    Http::assertSentCount(2);

    Http::assertSentInOrder([
        function ($request) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
            return str_starts_with($request->url(), self::GOOGLE_BOOKS_API_URL)
                && ($query['q'] ?? null) === 'isbn:4101000012';
        },
        function ($request) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
            // convertIsbn10To13('4101000012') は 9784101000015 を返す（API レスポンスの ISBN は 9784101000018）
            return str_starts_with($request->url(), self::GOOGLE_BOOKS_API_URL)
                && ($query['q'] ?? null) === 'isbn:9784101000015';
        },
    ]);
}

#[Test]
public function rate_limit_429_returns_empty_books_for_keyword_search(): void
{
    $body = $this->loadFixture('error_429');

    Http::fake([
        self::GOOGLE_BOOKS_API_URL . '*' => Http::response($body, 429),
    ]);

    $service = $this->getBookService();
    $result = $service->search('キーワード429テスト');

    $this->assertSame([], $result['books']);
    $this->assertSame(['q' => 'キーワード429テスト'], $result['filters']);
    $this->assertTrue($result['hasSearched']);
}


    #[Test]
    public function fixture_empty_response_returns_empty_books_without_error(): void
    {
        $fixture = $this->loadFixture('empty');
        $this->assertSame(0, $fixture['totalItems']);
        $this->assertSame([], $fixture['items']);

        Http::fake([self::GOOGLE_BOOKS_API_URL . '*' => Http::response($fixture, 200)]);
        $service = $this->getBookService();
        $result = $service->search('存在しないキーワードxyz');
        $this->assertSame([], $result['books']);
        $this->assertTrue($result['hasSearched']);
    }
}
