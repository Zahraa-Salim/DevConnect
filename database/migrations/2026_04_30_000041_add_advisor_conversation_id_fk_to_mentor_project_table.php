<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Deferred FK: mentor_project.advisor_conversation_id → conversations.id
    public function up(): void
    {
        Schema::table('mentor_project', function (Blueprint $table) {
            $table->foreign('advisor_conversation_id')->references('id')->on('conversations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('mentor_project', function (Blueprint $table) {
            $table->dropForeign(['advisor_conversation_id']);
        });
    }
};
