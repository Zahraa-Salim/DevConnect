<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('help_requests', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete()->after('id');
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete()->after('user_id');
            $table->string('title')->nullable()->after('project_id');
            $table->text('description')->nullable()->after('title');
            $table->json('tech_tags')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('help_requests', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['project_id']);
            $table->dropColumn(['user_id', 'project_id', 'title', 'description', 'tech_tags']);
        });
    }
};
