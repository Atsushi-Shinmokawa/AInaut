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
        
        // 必須：どの本の要約か
        $table->foreignUuid('book_id')->constrained('books')->cascadeOnDelete();

        // 任意：誰のための要約か（NULLなら全ユーザー共通の「基本要約」とする）
        $table->foreignUuid('user_id')->nullable()->constrained('users')->cascadeOnDelete();

        $table->string('model_name')->comment('gpt-4o-miniなど');
        $table->text('content'); // 要約本文
        
        // 生成時の条件（「初心者向け」「300文字で」など）を保存しておくと、後で「なぜこの要約になったか」がわかる
        $table->string('context_type')->default('general')->comment('general, for_beginner, technical etc'); 

        $table->json('meta')->nullable(); 

        $table->timestamps();
        
        // 検索を速くするため、複合インデックスを貼る
        $table->index(['book_id', 'user_id']);
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
