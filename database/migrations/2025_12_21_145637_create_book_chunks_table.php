<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('book_chunks', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('user_id')->index();
            $table->uuid('book_id')->index();
            $table->uuid('book_document_id')->index();

            $table->unsignedInteger('chunk_index'); // 1..N
            $table->text('content');
            $table->unsignedInteger('char_length')->default(0);

            $table->timestamps();

            $table->unique(['book_document_id', 'chunk_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_chunks');
    }
};
