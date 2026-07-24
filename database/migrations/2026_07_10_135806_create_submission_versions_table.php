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
        Schema::create('submission_versions', function (Blueprint $table) {
               $table->id();
        $table->foreignId('submission_id')->constrained('submissions')->cascadeOnDelete();
        $table->integer('version_number');
        $table->foreignId('uploaded_by_id')->constrained('users')->restrictOnDelete();
        $table->text('upload_notes')->nullable();
        $table->timestamp('uploaded_at');
        $table->timestamps();

        $table->unique(['submission_id', 'version_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submission_versions');
    }
};
