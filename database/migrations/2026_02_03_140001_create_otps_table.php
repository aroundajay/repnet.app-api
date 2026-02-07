<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for OTPs (One-Time Passwords) table.
 * 
 * Stores OTP codes for user verification with:
 * - Support for different OTP types (login, update_password, etc.)
 * - Tracking of sent and expiration timestamps
 * - Soft deletes for audit trail
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('otps', function (Blueprint $table) {
            // Primary key as UUID
            $table->uuid('id')->primary();
            
            // Foreign key to users table
            $table->foreignUuid('user_id')->nullable();
            
            // Type of OTP (e.g., 'login', 'update_password', 'update_email')
            $table->string('type');

            // Encrypted OTP code
            $table->longText('otp');

            // Mobile / Email identifier
            $table->string('identifier');

            // Encrypted data
            $table->longText('data')->nullable();

            // failed attempts
            $table->integer('failed_attempts')->default(0);

            // last failed attempt at
            $table->timestamp('last_failed_attempt_at')->nullable();

            // last successful attempt at
            $table->timestamp('succeeded_at')->nullable();

            // Timestamps for OTP lifecycle
            $table->timestamp('sent_at');
            $table->timestamp('expired_at');

            // Standard Laravel timestamps and soft deletes
            $table->timestamps();
            $table->softDeletes();

            // Index for faster lookups by user and type
            $table->index(['user_id', 'type']);

            // Index for faster lookups by identifier
            $table->index('identifier');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('otps');
    }
};
