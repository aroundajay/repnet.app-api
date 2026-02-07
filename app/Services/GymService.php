<?php

namespace App\Services;

use App\Models\Gym;
use App\Models\GymUser;
use App\Repositories\GymRepository;
use App\Repositories\GymUserRepository;
use Illuminate\Support\Arr;

/**
 * Gym Service
 *
 * Handles gym-related business logic:
 * - Creating a gym with the given owner
 * - Assigning the owner as a GymUser with role OWNER and status active
 */
class GymService
{
    public function __construct(
        protected GymRepository $gymRepository,
        protected GymUserRepository $gymUserRepository
    ) {}

    /**
     * Create a new gym and assign the given user as owner.
     * Also creates a GymUser record so the owner appears in the gym's membership list.
     *
     * @param array $data Validated gym data: name, description?, location_lat, location_lng, is_public?
     * @param string $ownerUserId Current user's ID (gym owner)
     * @return Gym The created gym
     */
    public function create(array $data, string $ownerUserId): Gym
    {
        // Only pass fillable fields to the repository
        $gymData = array_merge(
            Arr::only($data, ['name', 'description', 'location_lat', 'location_lng', 'is_public']),
            ['user_id' => $ownerUserId]
        );

        // Ensure is_public has a boolean value (default false)
        if (!isset($gymData['is_public'])) {
            $gymData['is_public'] = false;
        }

        $gym = $this->gymRepository->create($gymData);

        // Add the owner as a gym member with role OWNER and status active
        $this->gymUserRepository->create([
            'gym_id' => $gym->id,
            'user_id' => $ownerUserId,
            'role' => \App\Models\GymUser::ROLE_OWNER,
            'status' => \App\Models\GymUser::STATUS_ACTIVE,
        ]);

        return $gym;
    }

    public function findGymUserByGymIdAndUserId(string $gymId, string $userId): ?GymUser
    {
        return $this->gymUserRepository->findByGymAndUser($gymId, $userId);
    }

    public function createGymUser(string $gymId, string $userId, array $data): GymUser
    {
        return $this->gymUserRepository->create(array_merge($data, [
            'gym_id' => $gymId,
            'user_id' => $userId,
        ]));
    }

    public function updateGymUser(string $gymId, string $userId, array $data): bool
    {
        return $this->gymUserRepository->updateGymUser($gymId, $userId, $data);
    }

    public function findGym(string $gymId): ?Gym
    {
        return $this->gymRepository->findById($gymId);
    }
}
