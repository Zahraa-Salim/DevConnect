<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Mentor-specific data. 1:1 with users. Only mentors have a row.
    public function up(): void
    {
        Schema::create('mentor_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->unique();
            $table->enum('status', ['approved', 'suspended', 'revoked'])->default('approved');
            $table->unsignedTinyInteger('experience_years');
            $table->json('topics');
            $table->json('domains')->nullable();
            $table->unsignedTinyInteger('hours_per_week');
            $table->integer('github_score')->nullable();
            $table->decimal('avg_rating', 3, 2)->default(0.00);
            $table->integer('sessions_completed')->default(0);
            $table->integer('projects_advised')->default(0);
            $table->date('reapply_after')->nullable();
            $table->timestamp('auto_approved_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'avg_rating']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mentor_profiles');
    }
};
