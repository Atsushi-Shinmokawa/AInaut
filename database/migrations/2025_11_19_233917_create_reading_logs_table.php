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
    Schema::create('reading_logs', function (Blueprint $table) {
        $table->uuid('id')->primary();
        
        $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
        $table->foreignUuid('book_id')->constrained('books')->cascadeOnDelete();

        // Level 3: 詳細な追跡のためステータスと日付を分ける
        $table->enum('status', ['want_to_read', 'reading', 'completed'])->default('want_to_read');
        $table->date('started_at')->nullable();
        $table->date('completed_at')->nullable();
        
        $table->unsignedTinyInteger('rating')->nullable()->comment('1-5の評価'); 
        
        $table->timestamps();
        
        // 1ユーザー1冊につきログは1つ（再読対応なら外すが、基本はこれでOK）
        $table->unique(['user_id', 'book_id']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reading_logs');
    }
};
