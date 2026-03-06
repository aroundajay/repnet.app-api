<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for gyms table.
 * 
 * Stores gym information with:
 * - Location coordinates for mapping/discovery
 * - Public/private visibility setting
 * - Owner reference via user_id
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('gyms', function (Blueprint $table) {
            // Primary key as UUID
            $table->uuid('id')->primary();

            // Gym details
            $table->string('name');
            $table->text('description')->nullable();

            // Location coordinates for mapping
            $table->decimal('location_lat', 10, 8);
            $table->decimal('location_lng', 11, 8);

            // Visibility setting (public gyms can be discovered by anyone)
            $table->boolean('is_public')->default(false);

            // Foreign key to users table (gym owner/creator)
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();

            // Standard Laravel timestamps and soft deletes
            $table->timestamps();
            $table->softDeletes();

            // Index for location-based queries
            $table->index(['location_lat', 'location_lng']);

            $table->index(
                ['is_public', 'location_lat', 'location_lng'],
                'gyms_public_location_idx'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gyms');
    }
};
