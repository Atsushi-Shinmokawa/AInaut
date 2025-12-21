<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('book_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('user_id')->index();
            $table->uuid('book_id')->index();

            $table->string('source', 30); // upload_txt / aozora_fetch
            $table->text('source_url')->nullable(); // 青空文庫URLなど
            $table->string('original_filename')->nullable();

            $table->string('storage_path'); // storage/app/.. の相対パス
            $table->unsignedInteger('text_length')->default(0);

            $table->timestamps();

            // FKは必要なら後で
            // $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            // $table->foreign('book_id')->references('id')->on('books')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_documents');
    }
};
