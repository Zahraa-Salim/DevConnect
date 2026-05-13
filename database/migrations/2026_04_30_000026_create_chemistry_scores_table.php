<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Snapshots of team compatibility when new member joins.
    public function up(): void
    {
        Schema::create('chemistry_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('triggered_by')->constrained('users')->cascadeOnDelete();
            $table->json('score_data');
            $table->timestamps();

            $table->index(['project_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chemistry_scores');
    }
};
