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
        
        $table->foreignUuid('reading_log_id')->constrained('reading_logs')->cascadeOnDelete();

        $table->string('type');
        $table->string('prompt_version')->nullable();
        $table->text('content');
        $table->string('persona')->default('zundamon');
        $table->json('meta')->nullable();

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
