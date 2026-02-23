<?php

namespace App\Services;

use App\Models\Gym;
use App\Models\GymUser;
use App\Repositories\GymRepository;
use App\Repositories\GymUserRepository;
use Illuminate\Contracts\Pagination\CursorPaginator;
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

        // sync metadata
        if (!empty($data['metadata'])) {
            $gym->updateMetadata($data['metadata']);
        }

        // sync files
        if (!empty($data['files'])) {
            $gym->files()->sync($data['files']);
        }

        // sync amenities
        if (!empty($data['amenities'])) {
            $gym->amenities()->sync($data['amenities']);
        }

        // sync workout types
        if (!empty($data['workout_types'])) {
            $gym->workoutTypes()->sync($data['workout_types']);
        }

        // Add the owner as a gym member with role OWNER and status active
        $this->gymUserRepository->create([
            'gym_id' => $gym->id,
            'user_id' => $ownerUserId,
            'role' => \App\Models\GymUser::ROLE_OWNER,
            'status' => \App\Models\GymUser::STATUS_ACTIVE,
        ]);

        return $gym->fresh(['files', 'amenities', 'workoutTypes']);
    }

    /**
     * Update a gym record.
     * @param string $gymId
     * @param array $data
     * @return Gym
     */
    public function update(string $gymId, array $data): Gym
    {
        $gym = $this->gymRepository->update($gymId, $data);

        // sync metadata
        if (!empty($data['metadata'])) {
            $gym->updateMetadata($data['metadata']);
        }

        // sync files
        if (!empty($data['files'])) {
            $gym->files()->sync($data['files']);
        }

        // sync amenities
        if (!empty($data['amenities'])) {
            $gym->amenities()->sync($data['amenities']);
        }

        // sync workout types
        if (!empty($data['workout_types'])) {
            $gym->workoutTypes()->sync($data['workout_types']);
        }

        return $gym->fresh(['files', 'amenities', 'workoutTypes']);
    }

    /**
     * List public gyms with cursor pagination.
     * Optionally sorted by distance from given coordinates.
     * Delegates to the repository for the actual query logic.
     *
     * @param array $data Optional: latitude, longitude, per_page, q (search by name)
     * @return CursorPaginator Cursor-paginated gym results
     */
    public function list(array $data): CursorPaginator
    {
        $latitude = $data['latitude'] ?? null;
        $longitude = $data['longitude'] ?? null;
        $perPage = (int) ($data['per_page'] ?? 15);
        $search = isset($data['q']) && $data['q'] !== '' ? (string) $data['q'] : null;
        $with = $data['with'] ?? [];

        // When coordinates are provided, fetch gyms sorted by distance
        if ($latitude !== null && $longitude !== null) {
            return $this->gymRepository->listSortedByDistance(
                (float) $latitude,
                (float) $longitude,
                $perPage,
                $search,
                $with
            );
        }

        // No coordinates; return all public gyms ordered by newest first
        return $this->gymRepository->listPublic($perPage, $search, $with);
    }

    public function findGymUserByGymIdAndUserId(string $gymId, string $userId, array $with = []): ?GymUser
    {
        return $this->gymUserRepository->findByGymAndUser($gymId, $userId, $with);
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

    public function findGym(string $gymId, array $with = []): ?Gym
    {
        return $this->gymRepository->findById($gymId, $with);
    }

    /**
     * List members of a gym with cursor pagination.
     *
     * Delegates query logic to GymUserRepository::listByGym().
     * Each item in the paginator is a GymUser with the related User eager-loaded.
     *
     * @param string $gymId UUID of the target gym
     * @param array  $data  Validated params: q?, status?, per_page?, cursor?
     * @return CursorPaginator
     */
    public function listUsers(string $gymId, array $data): CursorPaginator
    {
        $perPage = (int) ($data['per_page'] ?? 50);
        $search  = isset($data['q']) && $data['q'] !== '' ? (string) $data['q'] : null;
        $status  = isset($data['status']) && $data['status'] !== '' ? (string) $data['status'] : null;

        return $this->gymUserRepository->listByGym(
            gymId: $gymId,
            perPage: $perPage,
            search: $search,
            status: $status,
        );
    }
}
