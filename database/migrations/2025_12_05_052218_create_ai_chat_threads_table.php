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
        Schema::create('ai_chat_threads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // 外部キー制約（ユーザーが消えたらスレッドも消す）
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            
            // 本に紐づく会話（NULL許可: 本に関係ない雑談もあり得る場合）
            $table->foreignUuid('book_id')->nullable()->constrained('books')->cascadeOnDelete(); 
            
            $table->string('title')->nullable(); // 会話のタイトル（後からAIが自動生成など）
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_chat_threads');
    }
};