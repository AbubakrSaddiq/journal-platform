<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
{
    Schema::create('journals', function (Blueprint $table) {
        $table->id();
        $table->string('title')->unique();
        $table->string('slug')->unique();
        $table->string('issn')->nullable()->unique();
        $table->text('description')->nullable();
        $table->json('settings')->default('{}');
        $table->timestamp('published_at')->nullable();
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('journals');
}
};
