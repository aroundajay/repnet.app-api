<?php

namespace App\Repositories;

use App\Models\GymUser;

/**
 * GymUser Repository
 *
 * Handles database operations for gym membership (GymUser model).
 * Used when assigning the owner to a new gym or managing memberships.
 */
class GymUserRepository
{
    /*
    |--------------------------------------------------------------------------
    | Create Operations
    |--------------------------------------------------------------------------
    */

    /**
     * Create a new gym membership record.
     *
     * @param array $data gym_id, user_id, role, optional membership_end, status
     * @return GymUser The created gym user model
     */
    public function create(array $data): GymUser
    {
        return GymUser::create($data);
    }

    /*
    |--------------------------------------------------------------------------
    | Read Operations
    |--------------------------------------------------------------------------
    */

    /**
     * Find a gym membership by gym and user.
     *
     * @param string $gymId Gym UUID
     * @param string $userId User UUID
     * @return GymUser|null
     */
    public function findByGymAndUser(string $gymId, string $userId, array $with = []): ?GymUser
    {
        return GymUser::with($with)
            ->where('gym_id', $gymId)
            ->where('user_id', $userId)
            ->first();
    }

    public function updateGymUser(string $gymId, string $userId, array $data): bool
    {
        return GymUser::where('gym_id', $gymId)
            ->where('user_id', $userId)
            ->update($data);
    }
}
