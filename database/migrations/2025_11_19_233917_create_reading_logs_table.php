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
        
        // UserモデルもUUIDにする必要があります（後述）
        $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
        $table->foreignUuid('book_id')->constrained('books')->cascadeOnDelete();

        $table->date('date')->nullable();
        $table->enum('progress', ['want_to_read', 'reading', 'finished'])->default('want_to_read');
        $table->text('note')->nullable();
        $table->json('tags')->nullable();

        $table->timestamps();
        
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
