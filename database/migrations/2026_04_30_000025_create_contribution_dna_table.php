<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Behavioral fingerprint per user. 1:1 with users. Updated by scheduler.
    public function up(): void
    {
        Schema::create('contribution_dna', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->unique();
            $table->json('role_pattern');
            $table->json('phase_activity');
            $table->json('issue_type_preference');
            $table->json('work_pattern');
            $table->timestamp('updated_at');

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contribution_dna');
    }
};
