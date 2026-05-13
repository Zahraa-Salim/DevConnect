<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // All projects (practice or real_client). Hub for tasks, chat, files, members.
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('idea_id')->nullable()->constrained('project_ideas')->nullOnDelete();
            $table->string('title', 200);
            $table->text('description');
            $table->enum('status', ['open', 'active', 'completed', 'at_risk', 'archived'])->default('open');
            $table->enum('type', ['practice', 'real_client']);
            $table->string('domain', 80)->nullable();
            $table->json('tech_stack')->nullable();
            $table->string('repo_url', 500)->nullable();
            $table->unsignedTinyInteger('max_members')->default(5);
            $table->string('estimated_duration', 50)->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'type']);
            $table->index(['domain', 'status']);
            $table->index(['owner_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
