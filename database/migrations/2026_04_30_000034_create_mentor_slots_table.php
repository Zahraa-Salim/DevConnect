<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Individual bookable time slots. Replaces unstructured JSON.
    public function up(): void
    {
        Schema::create('mentor_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mentor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('office_hour_id')->nullable()->constrained('office_hours')->nullOnDelete();
            $table->dateTime('slot_datetime');
            $table->boolean('is_booked')->default(false);
            $table->timestamps();

            $table->index(['mentor_id', 'slot_datetime', 'is_booked']);
            $table->index('slot_datetime');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mentor_slots');
    }
};
