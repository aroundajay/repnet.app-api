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
        Schema::create('gym_workout_types', function (Blueprint $table) {
            $table->foreignUuid('gym_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('workout_type_id')->constrained()->cascadeOnDelete();
            $table->primary(['gym_id', 'workout_type_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gym_workout_types');
    }
};
