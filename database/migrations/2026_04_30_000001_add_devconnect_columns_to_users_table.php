<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Core identity. Add DevConnect-specific columns to existing users table.
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('github_username', 100)->nullable()->unique();
            $table->text('github_token')->nullable();
            $table->timestamp('github_synced_at')->nullable();
            $table->text('bio')->nullable();
            $table->string('avatar_url', 500)->nullable();
            $table->enum('role', ['dev', 'designer', 'pm', 'mentor', 'exploring'])->default('exploring');
            $table->boolean('is_available')->default(true);
            $table->boolean('is_verified')->default(false);
            $table->decimal('reputation_score', 5, 2)->default(0.00);
            $table->timestamp('notifications_read_at')->nullable();

            $table->index(['role', 'is_available']);
            $table->index(['reputation_score']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['reputation_score']);
            $table->dropIndex(['role', 'is_available']);
            $table->dropColumn([
                'github_username',
                'github_token',
                'github_synced_at',
                'bio',
                'avatar_url',
                'role',
                'is_available',
                'is_verified',
                'reputation_score',
                'notifications_read_at',
            ]);
        });
    }
};
