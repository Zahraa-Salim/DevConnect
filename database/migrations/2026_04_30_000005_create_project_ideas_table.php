<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // All idea sources (AI, community, curated) in one table.
    public function up(): void
    {
        Schema::create('project_ideas', function (Blueprint $table) {
            $table->id();
            $table->enum('source', ['ai', 'community', 'curated']);
            $table->string('title', 200);
            $table->text('description');
            $table->string('domain', 80)->nullable();
            $table->enum('difficulty', ['beginner', 'intermediate', 'advanced']);
            $table->tinyInteger('team_size')->default(3);
            $table->json('suggested_roles')->nullable();
            $table->json('requirements')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['pending', 'active', 'featured', 'converted', 'removed'])->default('active');
            $table->integer('upvotes')->default(0);
            $table->integer('comments_count')->default(0);
            $table->unsignedBigInteger('converted_project_id')->nullable();
            $table->timestamps();

            $table->index(['source', 'status']);
            $table->index(['difficulty', 'domain']);
            $table->index(['upvotes']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_ideas');
    }
};
