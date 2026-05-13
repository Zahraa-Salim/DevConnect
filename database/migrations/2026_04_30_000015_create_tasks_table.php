<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Kanban tasks per project. Living Board.
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('parent_task_id')->nullable()->constrained('tasks')->nullOnDelete();
            $table->string('role_tag', 80)->nullable();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->enum('energy', ['quick_win', 'deep_work', 'blocked'])->nullable();
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->enum('status', ['todo', 'in_progress', 'done'])->default('todo');
            $table->integer('position')->default(0);
            $table->date('due_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status', 'position']);
            $table->index(['assigned_to', 'status']);
            $table->index(['role_tag', 'status']);
            $table->index('parent_task_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
