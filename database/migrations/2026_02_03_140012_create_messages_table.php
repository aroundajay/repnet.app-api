<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for messages table.
 * 
 * Individual messages within conversation threads with:
 * - Thread association for grouping
 * - Sender tracking via user_id
 * - Soft deletes for message deletion
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            // Primary key as UUID
            $table->uuid('id')->primary();
            
            // Foreign key to message_threads table
            $table->foreignUuid('thread_id')->constrained('message_threads')->cascadeOnDelete();

            // Polymorphic relationship to messageable entities
            // Can be: users and gyms
            $table->uuidMorphs('sender');

            // Message content
            $table->text('message')->nullable();

            // Location coordinates for mapping
            $table->decimal('location_lat', 10, 8)->nullable();
            $table->decimal('location_lng', 11, 8)->nullable();

            // Visibility setting (public gyms can be discovered by anyone)
            $table->boolean('is_public')->default(false);

            // Card type
            $table->enum('card_type', [
                'POST',
                'NEW_MEMBER',
                'DM',
                'COMMENT',
            ])->default('POST');

            // Standard Laravel timestamps and soft deletes
            $table->timestamps();
            $table->softDeletes();

            // Index for location-based queries
            $table->index(['thread_id', 'location_lat', 'location_lng', 'is_public', 'created_at']);
            $table->index(
                ['card_type', 'is_public', 'location_lat', 'location_lng'],
                'messages_public_posts_geo_idx'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
