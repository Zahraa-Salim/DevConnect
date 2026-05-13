<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sprints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('name', 100);
            $table->text('goal')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['planning', 'active', 'completed'])->default('planning');
            $table->integer('velocity')->nullable();
            $table->text('retro_good')->nullable();
            $table->text('retro_improve')->nullable();
            $table->text('retro_actions')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sprints');
    }
};
