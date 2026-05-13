<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_usage_logs', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('user_id')->constrained('projects')->nullOnDelete();
        });

        // Extend the feature enum to include task_gen (MySQL only — SQLite uses varchar and needs no change)
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE ai_usage_logs MODIFY COLUMN feature ENUM('idea_gen','project_match','issue_match','profile_suggest','chemistry','dna','task_gen')");
        }
    }

    public function down(): void
    {
        Schema::table('ai_usage_logs', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropColumn('project_id');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE ai_usage_logs MODIFY COLUMN feature ENUM('idea_gen','project_match','issue_match','profile_suggest','chemistry','dna')");
        }
    }
};
