<?php

namespace App\Repositories;

use App\Models\Gym;
use Illuminate\Contracts\Pagination\CursorPaginator;

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
    | Update Operations
    |--------------------------------------------------------------------------
    */

    /**
     * Update a gym record.
     *
     * @param string $gymId Gym UUID
     * @param array $data Gym data (name, description, location_lat, location_lng, is_public, user_id)
     * @return Gym The updated gym model
     */
    public function update(string $gymId, array $data): Gym
    {
        $gym = $this->findById($gymId);
        $gym->update($data);

        return $gym->fresh();
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
     * @param array $with default []
     * @return Gym|null
     */
    public function findById(string $id, $with = []): ?Gym
    {
        return Gym::with($with)->findOrFail($id);
    }

    /**
     * List all public gyms with cursor pagination, newest first.
     * Optionally filter by gym name (partial match).
     *
     * @param int $perPage Number of items per page
     * @param string|null $search If set, filter by name LIKE %search%
     * @return CursorPaginator
     */
    public function listPublic(int $perPage = 15, ?string $search = null, array $with = []): CursorPaginator
    {
        $query = Gym::with($with)->where('is_public', true);

        if ($search !== null && $search !== '') {
            $query->where('name', 'like', $this->likePattern($search));
        }

        return $query
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc') // deterministic tie-breaker for cursor pagination
            ->cursorPaginate($perPage);
    }

    /**
     * List all public gyms sorted by distance with cursor pagination.
     * Uses the Haversine formula to calculate distance in kilometers.
     *
     * Uses a subquery approach so that `distance_km` becomes a real column
     * in the derived table. This is necessary because MySQL cannot reference
     * column aliases in WHERE clauses, which cursor pagination needs for page 2+.
     *
     * Haversine formula:
     *   d = 6371 * acos(
     *       cos(radians(lat1)) * cos(radians(lat2)) * cos(radians(lng2) - radians(lng1))
     *       + sin(radians(lat1)) * sin(radians(lat2))
     *   )
     *
     * @param float $latitude  User's latitude (-90 to 90)
     * @param float $longitude User's longitude (-180 to 180)
     * @param int $perPage     Number of items per page
     * @param string|null $search If set, filter by gym name (partial match)
     * @return CursorPaginator Gyms with distance_km, sorted ascending
     */
    public function listSortedByDistance(float $latitude, float $longitude, int $perPage = 15, ?string $search = null, array $with = []): CursorPaginator
    {
        // Haversine formula calculates great-circle distance between two points
        // 6371 = Earth's radius in kilometers
        $haversine = "(
            6371 * acos(
                cos(radians(?))
                * cos(radians(location_lat))
                * cos(radians(location_lng) - radians(?))
                + sin(radians(?))
                * sin(radians(location_lat))
            )
        )";

        // Inner query: compute distance_km for each public gym
        $innerQuery = Gym::query()
            ->select('gyms.*')
            ->selectRaw("{$haversine} AS distance_km", [$latitude, $longitude, $latitude])
            ->where('is_public', true);

        if ($search !== null && $search !== '') {
            $innerQuery->where('name', 'like', $this->likePattern($search));
        }

        // Outer query: wraps inner as a subquery so distance_km is a real column.
        // This allows cursor pagination to reference distance_km in WHERE clauses.
        // withoutGlobalScopes() prevents duplicate soft-delete conditions on outer query.
        return Gym::with($with)
            ->withoutGlobalScopes()
            ->fromSub($innerQuery, 'gyms')
            ->orderBy('distance_km', 'asc')
            ->orderBy('id', 'asc') // deterministic tie-breaker for cursor pagination
            ->cursorPaginate($perPage);
    }

    /**
     * Build a LIKE pattern for partial name match.
     * Escapes SQL LIKE wildcards (% and _) so user input is treated literally.
     *
     * @param string $value User search string
     * @return string Pattern suitable for WHERE name LIKE ?
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
