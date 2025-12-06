<?php

// database/seeders/BookSeeder.php

namespace Database\Seeders;

use App\Models\Author;
use App\Models\Book;
use Illuminate\Database\Seeder;

class BookSeeder extends Seeder
{
    public function run(): void
    {
        $authors = Author::all();

        Book::factory()
            ->count(40)
            ->make()
            ->each(function (Book $book) use ($authors) {
                $book->author_id = $authors->random()->id;
                $book->save();
            });
    }
}

