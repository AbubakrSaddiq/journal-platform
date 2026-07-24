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
        Schema::create('editorial_assessments', function (Blueprint $table) {
               $table->id();
        $table->foreignId('submission_id')->constrained('submissions')->cascadeOnDelete();
        $table->foreignId('assessed_by_id')->constrained('users')->restrictOnDelete();
        $table->string('decision'); // desk_reject, return_for_corrections, send_to_review
        $table->text('reason')->nullable();
        $table->timestamp('assessed_at');
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('editorial_assessments');
    }
};
