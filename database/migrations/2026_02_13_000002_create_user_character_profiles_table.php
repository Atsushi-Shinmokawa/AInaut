<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_character_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('character'); // ChatCharacter の value
            $table->string('nickname')->nullable();
            $table->string('speech_style')->default('friendly'); // friendly / polite / logical など
            $table->json('favorite_genres')->nullable();
            $table->text('custom_note')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'character']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_character_profiles');
    }
};

