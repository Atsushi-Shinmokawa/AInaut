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
    Schema::create('reading_notes', function (Blueprint $table) {
        $table->uuid('id')->primary();
        
        // どのログ（誰のどの本）に対するメモか
        $table->foreignUuid('reading_log_id')->constrained('reading_logs')->cascadeOnDelete();

        $table->integer('page_number')->nullable(); // ページ数
        $table->text('content'); // メモ内容
        
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reading_notes');
    }
};
