<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\ReadingLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReadingLogFactory extends Factory
{
    protected $model = ReadingLog::class;

    public function definition(): array
    {
        $status = $this->faker->randomElement([
            'want_to_read',
            'reading',
            'completed',
        ]);

        $startedAt = null;
        $completedAt = null;

        if ($status !== 'want_to_read') {
            $startedAt = $this->faker->dateTimeBetween('-2 months', '-1 week');
        }

        if ($status === 'completed' && $startedAt) {
            $completedAt = $this->faker->dateTimeBetween($startedAt, 'now');
        }

        return [
            'user_id'      => User::factory(),  // Seeder側で上書きする予定
            'book_id'      => Book::factory(),  // これも後で上書きする
            'status'       => $status,
            'started_at'   => $startedAt,
            'completed_at' => $completedAt,
            'rating' => $status === 'completed'
    ? $this->faker->numberBetween(1, 5)
    : null,
        ];
    }
}
