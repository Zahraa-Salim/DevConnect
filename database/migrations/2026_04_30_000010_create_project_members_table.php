<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Pivot: users participating in projects.
    public function up(): void
    {
        Schema::create('project_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role', 80)->nullable();
            $table->enum('status', ['undecided', 'active', 'left', 'removed'])->default('undecided');
            $table->unsignedTinyInteger('access_level')->default(1);
            $table->timestamp('joined_at');
            $table->timestamp('left_at')->nullable();

            $table->unique(['project_id', 'user_id']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_members');
    }
};
