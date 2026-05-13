<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mentor_slots', function (Blueprint $table) {
            $table->foreignId('mentor_profile_id')->nullable()->constrained('mentor_profiles')->cascadeOnDelete()->after('id');
            $table->dateTime('starts_at')->nullable()->after('mentor_profile_id');
            $table->dateTime('ends_at')->nullable()->after('starts_at');
            $table->string('status', 20)->default('available')->after('ends_at');
        });
    }

    public function down(): void
    {
        Schema::table('mentor_slots', function (Blueprint $table) {
            $table->dropForeign(['mentor_profile_id']);
            $table->dropColumn(['mentor_profile_id', 'starts_at', 'ends_at', 'status']);
        });
    }
};
