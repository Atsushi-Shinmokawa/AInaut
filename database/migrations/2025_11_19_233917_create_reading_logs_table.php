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
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('book_id');
            $table->foreign('book_id')->references('id')->on('books')->cascadeOnDelete();
    
            $table->date('date');
            $table->enum('progress', ['started', 'reading', 'finished'])->default('started');
            $table->text('note')->nullable();
            $table->json('tags')->nullable();
    
            $table->timestamps();
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
