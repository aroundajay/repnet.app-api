<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for challenges table.
 * 
 * Gym challenges/competitions with:
 * - Configurable metric types (reps, time, weight)
 * - Start and end date scheduling
 * - Reward description for winners
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('challenges', function (Blueprint $table) {
            // Primary key as UUID
            $table->uuid('id')->primary();
            
            // Foreign key to gyms table (which gym hosts this challenge)
            $table->foreignUuid('gym_id')->constrained()->cascadeOnDelete();
            
            // Challenge details
            $table->string('title');
            $table->text('description')->nullable();
            
            // Foreign key to workout_types table
            $table->foreignUuid('workout_type_id')->constrained()->cascadeOnDelete();
            
            // How submissions are measured (reps, time in seconds, weight in kg/lbs)
            $table->enum('metric_type', ['reps', 'time', 'weight']);
            
            // Challenge duration
            $table->date('start_date');
            $table->date('end_date');
            
            // Prize/reward description for winners
            $table->string('reward')->nullable();
            
            // Standard Laravel timestamps and soft deletes
            $table->timestamps();
            $table->softDeletes();
            
            // Index for finding active challenges
            $table->index(['gym_id', 'start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('challenges');
    }
};
