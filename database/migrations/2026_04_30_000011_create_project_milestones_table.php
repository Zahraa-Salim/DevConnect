<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Milestone definitions for milestone-based access on Real Client Projects.
    public function up(): void
    {
        Schema::create('project_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('order_index');
            $table->unsignedTinyInteger('unlocks_access_level')->default(1);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'order_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_milestones');
    }
};
