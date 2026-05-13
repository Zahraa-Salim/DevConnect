<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Per-call audit log for every Claude API call. Cost + abuse tracking.
    public function up(): void
    {
        Schema::create('ai_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('feature', ['idea_gen', 'project_match', 'issue_match', 'profile_suggest', 'chemistry', 'dna']);
            $table->string('model', 50);
            $table->integer('prompt_tokens');
            $table->integer('completion_tokens');
            $table->integer('latency_ms')->nullable();
            $table->enum('status', ['success', 'error'])->default('success');
            $table->text('error_message')->nullable();
            $table->timestamp('created_at');

            $table->index(['user_id', 'created_at']);
            $table->index(['feature', 'created_at']);
            $table->index(['created_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usage_logs');
    }
};
