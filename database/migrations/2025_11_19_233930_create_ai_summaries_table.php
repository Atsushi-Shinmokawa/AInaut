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
        Schema::create('ai_summaries', function (Blueprint $table) {
            $table->uuid('id')->primary();
        $table->uuid('book_id');
        $table->foreign('book_id')->references('id')->on('books')->cascadeOnDelete();

        $table->string('type'); // summary | insight
        $table->string('prompt_version')->nullable();
        $table->text('content'); // 要約 or 考察本文
        $table->string('persona')->default('zundamon');
        $table->json('meta')->nullable(); // tokens, model, cost など

        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_summaries');
    }
};
