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
        Schema::create('editing_tasks', function (Blueprint $table) {
           $table->id();
        $table->foreignId('submission_id')->constrained('submissions')->cascadeOnDelete();
        $table->foreignId('assigned_to_id')->constrained('users')->restrictOnDelete();
        $table->string('status')->default('in_progress'); // in_progress, author_review, completed
        $table->timestamp('assigned_at');
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('editing_tasks');
    }
};
