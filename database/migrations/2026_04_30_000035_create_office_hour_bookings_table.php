<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Confirmed/pending bookings of mentor slots.
    public function up(): void
    {
        Schema::create('office_hour_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mentor_slot_id')->constrained('mentor_slots')->cascadeOnDelete();
            $table->foreignId('booker_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled', 'no_show'])->default('pending');
            $table->string('session_topic', 200)->nullable();
            $table->text('outcome_notes')->nullable();
            $table->tinyInteger('rating')->nullable();
            $table->text('rating_comment')->nullable();
            $table->timestamps();

            $table->unique('mentor_slot_id');
            $table->index(['booker_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('office_hour_bookings');
    }
};
