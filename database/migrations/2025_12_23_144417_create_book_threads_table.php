<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('book_threads', function (Blueprint $table) {
      $table->uuid('id')->primary();

      $table->uuid('user_id')->index();
      $table->uuid('book_id')->index();

      $table->string('title')->nullable(); // v1は未使用でもOK
      $table->timestamps();

      $table->unique(['user_id', 'book_id']); // v1：本ごとに1スレッド
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('book_threads');
  }
};
