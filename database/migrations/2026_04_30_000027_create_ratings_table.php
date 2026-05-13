<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Multi-dimensional teammate ratings after project completion.
    public function up(): void
    {
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('rater_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('rated_id')->nullable()->constrained('users')->nullOnDelete();
            $table->tinyInteger('communication_score');
            $table->tinyInteger('reliability_score');
            $table->tinyInteger('contribution_score');
            $table->decimal('overall_score', 3, 2);
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'rater_id', 'rated_id']);
            $table->index(['rated_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};
