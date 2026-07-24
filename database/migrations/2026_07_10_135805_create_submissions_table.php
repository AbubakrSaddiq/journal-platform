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
        Schema::create('submissions', function (Blueprint $table) {
           $table->id();
        $table->foreignId('journal_id')->constrained('journals')->cascadeOnDelete();
        $table->foreignId('section_id')->constrained('sections')->cascadeOnDelete();
        $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
        
        $table->string('title');
        $table->text('abstract');
        $table->string('keywords')->nullable();
        $table->text('cover_letter')->nullable();
        $table->string('status')->default('submitted');
        $table->unsignedBigInteger('current_version_id')->nullable();
        
        $table->timestamp('submitted_at');
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
