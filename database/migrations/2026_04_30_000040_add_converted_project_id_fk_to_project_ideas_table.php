<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Deferred FK: project_ideas.converted_project_id → projects.id
    public function up(): void
    {
        Schema::table('project_ideas', function (Blueprint $table) {
            $table->foreign('converted_project_id')->references('id')->on('projects')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('project_ideas', function (Blueprint $table) {
            $table->dropForeign(['converted_project_id']);
        });
    }
};
