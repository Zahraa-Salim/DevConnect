<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Structured working style per user. 1:1 with users. Required by Team Chemistry AI.
    public function up(): void
    {
        Schema::create('working_styles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->unique();
            $table->enum('communication_pref', ['async', 'sync', 'mixed'])->nullable();
            $table->time('work_hours_start')->nullable();
            $table->time('work_hours_end')->nullable();
            $table->string('timezone', 50)->default('Asia/Beirut');
            $table->enum('feedback_style', ['direct', 'gentle', 'structured'])->nullable();
            $table->enum('conflict_approach', ['discuss', 'vote', 'defer'])->nullable();
            $table->tinyInteger('weekly_hours')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('working_styles');
    }
};
