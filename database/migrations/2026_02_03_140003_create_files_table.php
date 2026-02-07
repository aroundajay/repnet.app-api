<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for files table.
 * 
 * Centralized storage for uploaded files with:
 * - Support for different file types (IMAGE, VIDEO)
 * - Tracking of who uploaded the file
 * - Soft deletes with optional storage cleanup tracking
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('files', function (Blueprint $table) {
            // Primary key as UUID
            $table->uuid('id')->primary();
            
            // Type of file (IMAGE or VIDEO)
            $table->enum('type', ['IMAGE', 'VIDEO']);
            
            // Storage path to the file
            $table->string('path');
            
            // Foreign key to users table (who uploaded the file)
            $table->foreignUuid('uploaded_by')->constrained('users')->cascadeOnDelete();
            
            // Timestamp when file was deleted from actual storage
            // Null means file still exists in storage
            $table->timestamp('deleted_from_storage_at')->nullable();
            
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
        Schema::dropIfExists('files');
    }
};
