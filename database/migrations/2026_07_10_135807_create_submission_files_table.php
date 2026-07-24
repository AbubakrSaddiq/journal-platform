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
        Schema::create('submission_files', function (Blueprint $table) {
          $table->id();
        $table->foreignId('submission_version_id')->constrained('submission_versions')->cascadeOnDelete();
        $table->string('file_path');
        $table->string('original_filename');
        $table->string('file_type');
        $table->string('file_role')->default('manuscript');
        $table->unsignedBigInteger('file_size')->nullable();
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submission_files');
    }
};
