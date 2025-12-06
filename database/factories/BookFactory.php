<?php

namespace Database\Factories;

use App\Models\Author;
use App\Models\Book;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookFactory extends Factory
{
    protected $model = Book::class;

    public function definition(): array
    {
        return [
            // ⭐ Author も一緒に作る（or Seeder側で上書きしてもOK）
            'author_id' => Author::factory(),

            'title'       => $this->faker->sentence(3),          // 3語くらいのタイトル
            'isbn'        => $this->faker->unique()->isbn13(),   // 13桁ISBN
            'publisher'   => $this->faker->optional()->company(),
            'source_url'  => $this->faker->optional()->url(),
            'published_at'=> $this->faker->optional()
                                      ->dateTimeBetween('-10 years', 'now'),
            // json カラムは「配列」で返す（cast がよしなに保存してくれる）
            'tags'        => $this->faker->randomElements(
                ['技術書', 'ビジネス', '小説', '自己啓発', '哲学'],
                $this->faker->numberBetween(0, 3)
            ),
            'cover_url'   => $this->faker->optional()
                                         ->imageUrl(200, 300, 'books', true),
            'raw_api_response' => [
                'dummy' => true,
                'source' => 'factory',
            ],
        ];
    }
}
