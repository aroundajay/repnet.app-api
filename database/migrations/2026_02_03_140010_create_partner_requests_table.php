<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for partner_requests table.
 * 
 * Workout partner matching system with:
 * - Time slot preferences (start_time, end_time)
 * - Workout type and muscle group preferences
 * - Automatic expiration tracking
 * - Status workflow (open, accepted, expired)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('partner_requests', function (Blueprint $table) {
            // Primary key as UUID
            $table->uuid('id')->primary();
            
            // Foreign key to gyms table (which gym to find partner at)
            $table->foreignUuid('gym_id')->constrained()->cascadeOnDelete();
            
            // Foreign key to users table (who is looking for a partner)
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            
            // Foreign key to workout_types table (preferred workout)
            $table->foreignUuid('workout_type_id')->constrained()->cascadeOnDelete();
            
            // Preferred muscle group to work on
            $table->string('muscle_group');
            
            // Preferred workout time window
            $table->timestamp('start_time');
            $table->timestamp('end_time');
            
            // When this request should no longer be shown
            $table->timestamp('expiring_at');
            
            // Request status (open for matching, accepted, or expired)
            $table->enum('status', ['open', 'accepted', 'expired'])->default('open');
            
            // Standard Laravel timestamps and soft deletes
            $table->timestamps();
            $table->softDeletes();
            
            // Index for finding open requests at a gym
            $table->index(['gym_id', 'status', 'expiring_at']);
            
            // Index for finding user's requests
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_requests');
    }
};
