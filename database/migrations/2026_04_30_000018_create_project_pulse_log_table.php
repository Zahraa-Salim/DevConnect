<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Health snapshots from Project Pulse scheduler.
    public function up(): void
    {
        Schema::create('project_pulse_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->json('signals');
            $table->enum('status', ['nudge_sent', 'at_risk', 'resolved']);
            $table->timestamp('triggered_at');

            $table->index(['project_id', 'triggered_at']);
            $table->index(['status', 'triggered_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_pulse_log');
    }
};
