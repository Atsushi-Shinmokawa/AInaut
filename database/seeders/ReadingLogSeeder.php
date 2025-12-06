<?php

// database/seeders/ReadingLogSeeder.php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\ReadingLog;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReadingLogSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first() ?? User::factory()->create([
            'email' => 'demo@example.com',
        ]);

        $statuses = ['want_to_read', 'reading', 'completed'];

        Book::all()->each(function (Book $book) use ($user, $statuses) {
            ReadingLog::factory()->create([
                'user_id' => $user->id,
                'book_id' => $book->id,
                'status'  => $statuses[array_rand($statuses)],
            ]);
        });
    }
}

