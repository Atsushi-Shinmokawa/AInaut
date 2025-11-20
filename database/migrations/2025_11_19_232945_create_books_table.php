<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->uuid('id')->primary();           // UUIDv7は後でModel側で生成
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();

        $table->string('title');
        $table->string('author')->nullable();
        $table->string('isbn')->nullable()->index();
        $table->string('source_url')->nullable();
        $table->json('tags')->nullable();
        $table->string('cover_url')->nullable();

        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
