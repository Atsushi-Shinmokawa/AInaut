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
        Schema::create('ai_chat_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // スレッドIDへの外部キー（スレッドが消えたらメッセージも道連れ）
            $table->foreignUuid('chat_thread_id')->constrained('ai_chat_threads')->cascadeOnDelete();
            
            $table->enum('role', ['user', 'assistant']); // どちらの発言か
            $table->text('content'); // 発言内容
            $table->integer('token_usage')->nullable(); // APIコスト計算用
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_chat_messages');
    }
};