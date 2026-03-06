<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for message_threads table.
 * 
 * Polymorphic conversation containers that can be attached to:
 * - Gyms (group chat for gym members)
 * - Partner Requests (chat between workout partners)
 * - Workout Videos (comments/discussion on videos)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('message_threads', function (Blueprint $table) {
            // Primary key as UUID
            $table->uuid('id')->primary();
            
            // Polymorphic relationship to messageable entities
            // Can be: gyms, partner_requests, workout_videos
            $table->uuidMorphs('messageable');

            // disabled_at
            $table->timestamp('disabled_at')->nullable();
            
            // Standard Laravel timestamps and soft deletes
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_threads');
    }
};
