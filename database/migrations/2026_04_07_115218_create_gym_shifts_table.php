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
        Schema::create('gym_shifts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('gym_id')->constrained('gyms', 'id')->cascadeOnDelete();
            $table->enum('day_of_week', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']);
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('day_pass_enabled')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['gym_id', 'day_of_week']);
            $table->unique(['gym_id', 'day_of_week', 'start_time', 'end_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gym_shifts');
    }
};
