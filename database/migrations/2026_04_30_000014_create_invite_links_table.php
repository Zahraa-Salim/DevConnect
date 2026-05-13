<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Shareable invite links for project roles.
    public function up(): void
    {
        Schema::create('invite_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('role_id')->nullable()->constrained('project_roles')->nullOnDelete();
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at')->nullable();
            $table->integer('max_uses')->nullable();
            $table->integer('uses')->default(0);
            $table->timestamps();

            $table->index(['project_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invite_links');
    }
};
