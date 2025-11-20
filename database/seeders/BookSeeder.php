<?php

namespace Database\Seeders;

use App\Models\Domain\Book;
use Illuminate\Database\Seeder;

class BookSeeder extends Seeder
{
    public function run(): void
    {
        Book::factory()->count(15)->create([
            'user_id' => 1, // Breeze の最初のユーザー
        ]);
    }
}
