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
        Schema::create('review_invitations', function (Blueprint $table) {
            $table->id();
        $table->foreignId('submission_id')->constrained('submissions')->cascadeOnDelete();
        $table->foreignId('reviewer_id')->constrained('users')->restrictOnDelete();
        $table->string('status')->default('pending'); // pending, accepted, declined
        $table->timestamp('invited_at');
        $table->timestamp('responded_at')->nullable();
        $table->timestamps();

        $table->unique(['submission_id', 'reviewer_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('review_invitations');
    }
};
