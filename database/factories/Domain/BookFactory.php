<?php

namespace Database\Factories\Domain;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Domain\Book>
 */
class BookFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
{
    return [
        'title' => $this->faker->sentence(3),
        'author' => $this->faker->name(),
        'isbn' => $this->faker->isbn13(),
        'source_url' => $this->faker->url(),
        'tags' => ['tag1', 'tag2'],
        'cover_url' => null,
    ];
}

}
