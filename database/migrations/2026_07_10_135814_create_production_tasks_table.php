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
        Schema::create('production_tasks', function (Blueprint $table) {
             $table->id();
        $table->foreignId('submission_id')->constrained('submissions')->cascadeOnDelete();
        $table->foreignId('assigned_to_id')->constrained('users')->restrictOnDelete();
        $table->string('doi')->nullable()->unique();
        $table->string('pagination')->nullable();
        $table->json('metadata')->nullable();
        $table->json('outputs')->nullable(); // {pdf: path, html: path, xml: path}
        $table->string('status')->default('in_progress'); // in_progress, completed
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_tasks');
    }
};
