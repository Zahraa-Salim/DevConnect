<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // AI Profile Suggestions output (CV / portfolio / LinkedIn).
    public function up(): void
    {
        Schema::create('ai_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('source_type', ['project', 'contribution']);
            $table->bigInteger('source_id');
            $table->text('cv_text');
            $table->text('portfolio_text');
            $table->text('linkedin_text');
            $table->string('model', 50)->nullable();
            $table->integer('tokens_used')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_suggestions');
    }
};
