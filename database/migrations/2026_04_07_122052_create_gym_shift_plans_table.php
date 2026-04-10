<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('gym_shift_plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('gym_shift_id')->constrained('gym_shifts', 'id')->cascadeOnDelete();
            $table->foreignUuid('updated_by')->constrained('users', 'id')->cascadeOnDelete();
            $table->enum('currency', [
                'USD',
                'INR',
            ])->default('INR');
            $table->decimal('price', 10, 2);
            $table->integer('duration_minutes')->default(60);
            $table->boolean('personal_training_enabled')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gym_shift_plans');
    }
};
