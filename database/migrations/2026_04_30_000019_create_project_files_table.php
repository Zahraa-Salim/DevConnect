<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // File metadata for workspace Files tab. Files in S3/local storage.
    public function up(): void
    {
        Schema::create('project_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('file_name', 255);
            $table->string('file_url', 500);
            $table->bigInteger('file_size');
            $table->string('mime_type', 100);
            $table->timestamps();

            $table->index(['project_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_files');
    }
};
