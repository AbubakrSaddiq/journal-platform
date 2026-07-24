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
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
        $table->foreignId('journal_id')->constrained('journals')->cascadeOnDelete();
        $table->string('title');
        $table->string('slug');
        $table->text('description')->nullable();
        $table->integer('sort_order')->default(0);
        $table->timestamps();

        $table->unique(['journal_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};
