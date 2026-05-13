<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Self-reported and endorsed skills.
    public function up(): void
    {
        Schema::create('user_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('skill_name', 80);
            $table->tinyInteger('proficiency')->default(3);
            $table->boolean('is_endorsed')->default(false);
            $table->integer('endorsement_count')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'skill_name']);
            $table->index('skill_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_skills');
    }
};
