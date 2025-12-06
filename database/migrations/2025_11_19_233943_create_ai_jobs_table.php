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
        $table->uuid('id')->primary();
            
        $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
    
        $table->uuid('target_id')->nullable();
        $table->string('target_type')->nullable();
    
        $table->string('job_type');        // ← type → job_type に
        $table->string('status');          // pending, processing, completed, failed
        $table->json('payload')->nullable();
        $table->json('result')->nullable(); // 結果をJSONで持ちたいなら追加
        $table->text('error_message')->nullable();
    
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
