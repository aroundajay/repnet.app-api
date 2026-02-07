<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for workout_videos table.
 * 
 * Stores workout video content for gyms with:
 * - Association to gym, file, and workout type
 * - Muscle group categorization for filtering
 * - Title and description for content organization
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('workout_videos', function (Blueprint $table) {
            // Primary key as UUID
            $table->uuid('id')->primary();
            
            // Foreign key to gyms table (which gym owns this video)
            $table->foreignUuid('gym_id')->constrained()->cascadeOnDelete();
            
            // Foreign key to files table (the actual video file)
            $table->foreignUuid('file_id')->constrained()->cascadeOnDelete();
            
            // Video metadata
            $table->string('title');
            $table->text('description')->nullable();
            
            // Foreign key to workout_types table
            $table->foreignUuid('workout_type_id')->constrained()->cascadeOnDelete();
            
            // Muscle group for categorization (e.g., 'chest', 'back', 'legs')
            $table->string('muscle_group');
            
            // Standard Laravel timestamps and soft deletes
            $table->timestamps();
            $table->softDeletes();
            
            // Index for filtering by gym and workout type
            $table->index(['gym_id', 'workout_type_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workout_videos');
    }
};
