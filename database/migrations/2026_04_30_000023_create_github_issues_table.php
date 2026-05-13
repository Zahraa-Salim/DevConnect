<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Cached GitHub issues from daily sync. Source for AI matching.
    public function up(): void
    {
        Schema::create('github_issues', function (Blueprint $table) {
            $table->id();
            $table->string('repo_full_name', 200);
            $table->integer('issue_number');
            $table->string('title', 500);
            $table->text('body')->nullable();
            $table->string('url', 500);
            $table->json('labels')->nullable();
            $table->string('language', 50)->nullable();
            $table->enum('difficulty', ['beginner', 'intermediate', 'advanced'])->nullable();
            $table->enum('state', ['open', 'closed'])->default('open');
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('fetched_at');

            $table->unique(['repo_full_name', 'issue_number']);
            $table->index(['language', 'difficulty', 'state']);
            $table->index(['state', 'fetched_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('github_issues');
    }
};
