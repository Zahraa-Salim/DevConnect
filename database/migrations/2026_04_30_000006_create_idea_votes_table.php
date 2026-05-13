<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // One vote per user per idea. Replaces naive upvote integer.
    public function up(): void
    {
        Schema::create('idea_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('idea_id')->constrained('project_ideas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('created_at');

            $table->unique(['idea_id', 'user_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('idea_votes');
    }
};
