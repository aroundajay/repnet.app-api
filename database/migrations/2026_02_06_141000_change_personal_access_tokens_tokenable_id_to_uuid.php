<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fix personal_access_tokens.tokenable_id for UUID user primary keys.
 *
 * User model uses HasUuids, so tokenable_id must be uuid, not bigint.
 * Laravel's morphs() creates bigint; we need uuidMorphs().
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            // Drop the default morph index (tokenable_type, tokenable_id)
            $table->dropIndex(['tokenable_type', 'tokenable_id']);
        });

        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->dropColumn('tokenable_id');
        });

        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->uuid('tokenable_id')->after('id');
            $table->index(['tokenable_type', 'tokenable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->dropIndex(['tokenable_type', 'tokenable_id']);
        });

        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->dropColumn('tokenable_id');
        });

        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->unsignedBigInteger('tokenable_id')->after('id');
            $table->index(['tokenable_type', 'tokenable_id']);
        });
    }
};
