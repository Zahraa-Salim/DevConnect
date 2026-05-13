<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Office hours config per mentor. Display summary. Slots live in mentor_slots.
    public function up(): void
    {
        Schema::create('office_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mentor_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 150)->nullable();
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('duration_minutes')->default(30);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['mentor_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('office_hours');
    }
};
