<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Open and filled roles per project.
    public function up(): void
    {
        Schema::create('project_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('role_name', 80);
            $table->unsignedTinyInteger('slots')->default(1);
            $table->unsignedTinyInteger('filled')->default(0);
            $table->boolean('is_open')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'is_open']);
            $table->index(['role_name', 'is_open']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_roles');
    }
};
