<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mentor_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mentor_slot_id')->constrained('mentor_slots')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->text('topic');
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();

            $table->unique('mentor_slot_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mentor_bookings');
    }
};
