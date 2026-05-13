<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mentor_profiles', function (Blueprint $table) {
            $table->text('motivation')->nullable()->after('user_id');
            $table->json('focus_areas')->nullable()->after('motivation');
            $table->json('availability')->nullable()->after('focus_areas');
            $table->boolean('is_active')->default(true)->after('availability');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('mentor_profiles', function (Blueprint $table) {
            $table->dropColumn(['motivation', 'focus_areas', 'availability', 'is_active', 'approved_at', 'rejected_at']);
        });
    }
};
