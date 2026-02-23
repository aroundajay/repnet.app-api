<?php

namespace App\Repositories;

use App\Models\GymUser;
use Illuminate\Contracts\Pagination\CursorPaginator;

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

    /*
    |--------------------------------------------------------------------------
    | List / Pagination Operations
    |--------------------------------------------------------------------------
    */

    /**
     * List gym members for a given gym with cursor pagination.
     *
     * Eager-loads the related User so callers receive the full user object
     * alongside the membership meta-data (role, status, membership_end).
     *
     * @param string      $gymId    Gym UUID to filter by
     * @param int         $perPage  Items per page (default 50)
     * @param string|null $search   Optional: partial match on users.name OR users.email
     * @param string|null $status   Optional: exact match on gym_users.status
     * @return CursorPaginator
     */
    public function listByGym(
        string $gymId,
        int $perPage = 50,
        ?string $search = null,
        ?string $status = null,
    ): CursorPaginator {
        $query = GymUser::with('user')
            ->where('gym_id', $gymId);

        // Optional status filter
        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        }

        // Optional name / email search (join users table)
        if ($search !== null && $search !== '') {
            $lowerPattern = $this->likePattern(mb_strtolower($search));

            $query->whereHas('user', function ($q) use ($lowerPattern): void {
                $q->whereRaw('LOWER(name) LIKE ?', [$lowerPattern])
                  ->orWhereRaw('LOWER(email) LIKE ?', [$lowerPattern]);
            });
        }

        return $query
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc') // deterministic tie-breaker for cursor pagination
            ->cursorPaginate($perPage);
    }

    /*
    |--------------------------------------------------------------------------
    | Private Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Build a LIKE pattern for partial string matching.
     * Escapes SQL LIKE wildcards so user input is treated literally.
     *
     * @param string $value Raw search string
     * @return string Pattern suitable for WHERE col LIKE ?
     */
    private function likePattern(string $value): string
    {
        $escaped = str_replace(
            ['\\', '%', '_'],
            ['\\\\', '\\%', '\\_'],
            $value
        );

        return '%' . $escaped . '%';
    }
}
