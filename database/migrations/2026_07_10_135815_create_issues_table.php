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
        Schema::create('issues', function (Blueprint $table) {
             $table->id();
        $table->foreignId('journal_id')->constrained('journals')->cascadeOnDelete();
        $table->integer('volume');
        $table->integer('issue_number');
        $table->timestamp('published_at')->nullable();
        $table->timestamps();

        $table->unique(['journal_id', 'volume', 'issue_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('issues');
    }
};
