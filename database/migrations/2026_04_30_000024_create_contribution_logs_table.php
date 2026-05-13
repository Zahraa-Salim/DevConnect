<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Solo contribution tracker. One per (user, issue).
    public function up(): void
    {
        Schema::create('contribution_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('github_issue_id')->constrained('github_issues')->cascadeOnDelete();
            $table->enum('status', ['bookmarked', 'working', 'pr_submitted', 'merged'])->default('bookmarked');
            $table->string('pr_url', 500)->nullable();
            $table->foreignId('converted_project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'github_issue_id']);
            $table->index(['user_id', 'status']);
            $table->index(['status', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contribution_logs');
    }
};
