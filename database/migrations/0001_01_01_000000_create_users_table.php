<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for users table and related auth tables.
 * 
 * Users table stores core user information with:
 * - UUID primary key for better security and distribution
 * - Mobile and email as unique identifiers
 * - Soft deletes for data retention
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            // Primary key as UUID for better security and scalability
            $table->uuid('id')->primary();
            
            // Unique identifiers for authentication
            $table->string('mobile')->unique()->nullable();
            $table->string('email')->unique()->nullable();
            
            // Password is nullable to support OTP-only authentication
            $table->string('password')->nullable();
            
            // Email verification timestamp
            $table->timestamp('email_verified_at')->nullable();

            // Mobile verification timestamp
            $table->timestamp('mobile_verified_at')->nullable();

            // User profile information
            $table->string('name');
            $table->string('avatar')->nullable();

            // Standard Laravel timestamps and soft deletes
            $table->timestamps();
            $table->softDeletes();
        });

        // Password reset tokens table for email-based password recovery
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // Sessions table for database-driven session management
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignUuid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
