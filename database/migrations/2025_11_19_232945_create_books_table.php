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
    Schema::create('books', function (Blueprint $table) {
        // uuid型を主キーにする
        $table->uuid('id')->primary();
        
        // 著者IDは Authorsテーブルが普通のIDなら foreignId、
        // AuthorsもUUIDにするなら foreignUuid に合わせる必要があります。
        // 今回はAuthorsもUUID(ULID)にすると仮定して書きます。
        $table->foreignUuid('author_id')->constrained('authors')->cascadeOnDelete();
        

        $table->string('title');
        $table->string('isbn')->nullable()->index();
        $table->string('publisher')->nullable(); // 文字列で管理
        $table->string('source_url')->nullable();
        $table->date('published_at')->nullable();

        // APIからのレスポンス丸ごと保存用（BookMetaDataテーブルの代わり）
        $table->json('raw_api_response')->nullable();
        $table->json('tags')->nullable();
        $table->string('cover_url')->nullable();
    
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
