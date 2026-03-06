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
        Schema::create('reactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuidMorphs('reactable');
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('reaction', [
                'LIKE',
                'LAUGH',
                'WOW',
                'SAD',
                'CELEBRATE',
                'CLAP',
                'FIST_BUMP',
                'FLEX',
                'HIGH_FIVE',
                'PRAY',
                'SMIRK',
                'TEAR',
                'WINK',
                'FOLLOW',
            ]);
            $table->timestamps();

            $table->index(['reactable_id', 'reactable_type', 'reaction']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reactions');
    }
};
