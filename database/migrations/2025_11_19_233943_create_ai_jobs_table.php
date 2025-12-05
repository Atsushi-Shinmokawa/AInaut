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

        // ポリモーフィックリレーションの場合、IDがUUIDなら _id カラムも uuid型にする
        $table->uuid('target_id')->nullable();
        $table->string('target_type')->nullable();

        $table->string('type');   
        $table->string('status'); 
        $table->json('payload')->nullable(); 
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
