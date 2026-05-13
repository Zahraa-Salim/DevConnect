<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // NDAs and team agreements signed for Real Client Projects.
    public function up(): void
    {
        Schema::create('team_agreements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('type', ['nda', 'team_agreement']);
            $table->longText('document_text');
            $table->timestamp('signed_at');
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'user_id', 'type']);
            $table->index(['project_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_agreements');
    }
};
