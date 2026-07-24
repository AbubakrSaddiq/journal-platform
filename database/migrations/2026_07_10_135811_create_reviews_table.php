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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
        $table->foreignId('submission_id')->constrained('submissions')->cascadeOnDelete();
        $table->foreignId('review_invitation_id')->constrained('review_invitations')->cascadeOnDelete();
        $table->text('comments_for_editor')->nullable();
        $table->text('comments_for_author')->nullable();
        $table->string('recommendation'); // accept, minor_revision, major_revision, reject, resubmit
        $table->timestamp('submitted_at');
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
