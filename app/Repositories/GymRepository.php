<?php

namespace App\Repositories;

use App\Models\Gym;

/**
 * Gym Repository
 *
 * Handles all database operations for the Gym model.
 * Encapsulates data access so services stay free of query logic.
 */
class GymRepository
{
    /*
    |--------------------------------------------------------------------------
    | Create Operations
    |--------------------------------------------------------------------------
    */

    /**
     * Create a new gym record.
     *
     * @param array $data Gym data (name, description, location_lat, location_lng, is_public, user_id)
     * @return Gym The created gym model
     */
    public function create(array $data): Gym
    {
        return Gym::create($data);
    }

    /*
    |--------------------------------------------------------------------------
    | Read Operations
    |--------------------------------------------------------------------------
    */

    /**
     * Find a gym by ID.
     *
     * @param string $id Gym UUID
     * @return Gym|null
     */
    public function findById(string $id): ?Gym
    {
        return Gym::find($id);
    }
}
