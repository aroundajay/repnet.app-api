<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for challenge_submissions table.
 * 
 * User submissions for gym challenges with:
 * - Performance value (reps, time, or weight based on challenge)
 * - Video proof for verification
 * - Approval workflow (pending, approved, rejected)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('challenge_submissions', function (Blueprint $table) {
            // Primary key as UUID
            $table->uuid('id')->primary();
            
            // Foreign key to challenges table
            $table->foreignUuid('challenge_id')->constrained()->cascadeOnDelete();
            
            // Foreign key to users table (who submitted)
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            
            // Submission value (interpreted based on challenge's metric_type)
            // e.g., 50 reps, 120 seconds, 100 kg
            $table->decimal('value', 10, 2);
            
            // Path to video proof (stored in storage)
            $table->string('video_path');
            
            // Approval status (pending review, approved, or rejected)
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            
            // Standard Laravel timestamps and soft deletes
            $table->timestamps();
            $table->softDeletes();
            
            // Index for finding submissions by challenge and status
            $table->index(['challenge_id', 'status']);
            
            // Index for finding user's submissions
            $table->index(['user_id', 'challenge_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('challenge_submissions');
    }
};
