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
        Schema::create('ai_jobs', function (Blueprint $table) {
            $$table->uuid('id')->primary();
            $table->uuid('book_id')->nullable();
            $table->foreign('book_id')->references('id')->on('books')->nullOnDelete();
    
            $table->string('type');   // summary | insight など
            $table->string('status'); // pending | succeeded | failed
            $table->json('payload')->nullable(); // 投げたプロンプトの概要など
            $table->text('error')->nullable();
    
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_jobs');
    }
};
