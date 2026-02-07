<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for notice_posts table.
 * 
 * Notice board for gym announcements with:
 * - Association to specific gym
 * - Tracking of who posted the notice
 * - Rich text content support
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notice_posts', function (Blueprint $table) {
            // Primary key as UUID
            $table->uuid('id')->primary();
            
            // Foreign key to gyms table (which gym's notice board)
            $table->foreignUuid('gym_id')->constrained()->cascadeOnDelete();
            
            // Foreign key to users table (who posted the notice)
            $table->foreignUuid('posted_by')->constrained('users')->cascadeOnDelete();
            
            // Notice content (supports rich text)
            $table->text('content');
            
            // Standard Laravel timestamps and soft deletes
            $table->timestamps();
            $table->softDeletes();
            
            // Index for fetching notices by gym
            $table->index('gym_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notice_posts');
    }
};
