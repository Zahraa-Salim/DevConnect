<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Teammate-witnessed skill endorsements during a project.
    public function up(): void
    {
        Schema::create('skill_endorsements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('endorser_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('endorsed_id')->constrained('users')->cascadeOnDelete();
            $table->string('skill_name', 80);
            $table->timestamps();

            $table->unique(['project_id', 'endorser_id', 'endorsed_id', 'skill_name'], 'endorsements_unique');
            $table->index(['endorsed_id', 'skill_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skill_endorsements');
    }
};
