<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('book_highlights', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('user_id')->index();
            $table->uuid('book_id')->nullable()->index(); // 紐付けは後で確定でもOK

            $table->string('source', 50); // kindle_email / kindle_clippings / manual
            $table->string('title_raw')->nullable(); // 取り込み時点の書名

            $table->string('location')->nullable();
            $table->string('page')->nullable();
            $table->dateTime('highlighted_at')->nullable();

            $table->text('content');

            $table->char('content_hash', 64)->unique(); // sha256 hex

            $table->timestamps();

            // FKは後でもOK
            // $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            // $table->foreign('book_id')->references('id')->on('books')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_highlights');
    }
};
