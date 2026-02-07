<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for gym_users table.
 * 
 * Role-based membership table connecting users to gyms with:
 * - Different roles (OWNER, TRAINER, MEMBER)
 * - Membership status tracking (pending, active, rejected)
 * - Optional membership expiration for paid memberships
 * - Composite unique key to prevent duplicate memberships
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('gym_users', function (Blueprint $table) {
            // Primary key as UUID
            $table->uuid('id')->primary();
            
            // Foreign keys to gyms and users tables
            $table->foreignUuid('gym_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            
            // Role within the gym (OWNER, TRAINER, or MEMBER)
            $table->enum('role', ['OWNER', 'TRAINER', 'MEMBER']);
            
            // Optional membership expiration date
            $table->timestamp('membership_end')->nullable();
            
            // Membership status (pending approval, active, or rejected)
            $table->enum('status', ['pending', 'active', 'rejected'])->default('pending');
            
            // Standard Laravel timestamps and soft deletes
            $table->timestamps();
            $table->softDeletes();
            
            // Composite unique key to prevent duplicate gym memberships
            $table->unique(['gym_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gym_users');
    }
};
