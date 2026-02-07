<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for workout_types table.
 * 
 * Extensible table for categorizing different workout types:
 * - Weightlifting, Yoga, CrossFit, etc.
 * - Can be extended by gym owners or admin
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('workout_types', function (Blueprint $table) {
            // Primary key as UUID
            $table->uuid('id')->primary();
            
            // Name of the workout type (e.g., 'Weightlifting', 'Yoga', 'CrossFit')
            $table->string('name');
            
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
        Schema::dropIfExists('workout_types');
    }
};
