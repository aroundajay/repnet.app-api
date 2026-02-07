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
 * - Bloom filter compatible read_by field for efficient read tracking
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
            
            // Foreign key to users table (message sender)
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            
            // Message content
            $table->text('message');
            
            // Bloom filter friendly field for tracking who has read the message
            // Using binary blob to store bloom filter bit array
            // This allows O(1) membership checks without storing individual user IDs
            $table->binary('read_by')->nullable();
            
            // Standard Laravel timestamps and soft deletes
            $table->timestamps();
            $table->softDeletes();
            
            // Index for fetching messages in a thread (ordered by creation)
            $table->index(['thread_id', 'created_at']);
            
            // Index for fetching user's messages
            $table->index('user_id');
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
