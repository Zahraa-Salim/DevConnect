<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Pivot — mentor advising a project. NOTE: advisor_conversation_id FK is DEFERRED.
    public function up(): void
    {
        Schema::create('mentor_project', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mentor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->unsignedBigInteger('advisor_conversation_id')->nullable();
            $table->enum('status', ['pending', 'accepted', 'declined', 'ended'])->default('pending');
            $table->text('advisory_notes')->nullable();
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->unique(['mentor_id', 'project_id']);
            $table->index(['project_id', 'status']);
            $table->index(['mentor_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mentor_project');
    }
};
