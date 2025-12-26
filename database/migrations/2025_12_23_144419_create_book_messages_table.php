<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('book_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('book_thread_id')->index();
            $table->uuid('user_id')->index();
            $table->uuid('book_id')->index(); // クエリ簡略化用（冗長だがv1ならOK）

            $table->string('role', 20); // user|assistant|system
            $table->text('content');
            $table->unsignedInteger('char_length')->default(0);

            $table->timestamps();

            $table->index(['book_thread_id', 'created_at']);

            $table->foreign('book_thread_id')->references('id')->on('book_threads')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('book_id')->references('id')->on('books')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_messages');
    }
};
