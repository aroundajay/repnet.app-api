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
     * ┌─────────────────────────────────────────────────────────────────────┐
     * │  Bounding-box pre-filter                                            │
     * │                                                                     │
     * │  Before computing Haversine for every public gym in the world, a   │
     * │  rectangular geographic envelope is applied to the inner query:     │
     * │                                                                     │
     * │    WHERE is_public = 1                                              │
     * │      AND location_lat BETWEEN min_lat AND max_lat    ← index range  │
     * │      AND location_lng BETWEEN min_lng AND max_lng    ← index range  │
     * │                                                                     │
     * │  This uses the (is_public, location_lat, location_lng) index so    │
     * │  MySQL seeks to the public subset and then range-scans only the    │
     * │  lat/lng window, reducing examined rows from O(N_public) to         │
     * │  O(rows_in_bbox).                                                   │
     * │                                                                     │
     * │  Rows inside the box but outside the exact radius are still        │
     * │  rejected by the Haversine in the SELECT, preserving correctness.  │
     * └─────────────────────────────────────────────────────────────────────┘
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
     * @param float       $latitude  User's latitude (-90 to 90)
     * @param float       $longitude User's longitude (-180 to 180)
     * @param float       $radiusKm  Search radius in kilometres for bounding-box pre-filter
     * @param int         $perPage   Number of items per page
     * @param string|null $search    If set, filter by gym name (partial match)
     * @param array       $with      Eager-load relations on final results
     * @return CursorPaginator Gyms with distance_km, sorted ascending
     */
    public function listSortedByDistance(
        float $latitude,
        float $longitude,
        float $radiusKm = 50.0,
        int $perPage = 15,
        ?string $search = null,
        array $with = [],
    ): CursorPaginator {
        // Haversine formula – Earth radius 6371 km
        $haversine = "
            6371 * acos(
                cos(radians(?))
                * cos(radians(location_lat))
                * cos(radians(location_lng) - radians(?))
                + sin(radians(?))
                * sin(radians(location_lat))
            )
        ";

        // Bounding box: converts the circle into a cheap rectangular pre-filter
        // that the (is_public, location_lat, location_lng) index can satisfy.
        $bbox = $this->computeBoundingBox($latitude, $longitude, $radiusKm);

        // Inner query: narrow to public gyms within the bounding box first,
        // then compute the exact distance_km for the surviving rows only.
        $innerQuery = Gym::query()
            ->select('gyms.*')
            ->selectRaw("{$haversine} AS distance_km", [$latitude, $longitude, $latitude])
            ->where('is_public', true)
            ->whereBetween('location_lat', [$bbox['min_lat'], $bbox['max_lat']])
            ->whereBetween('location_lng', [$bbox['min_lng'], $bbox['max_lng']]);

        if ($search !== null && $search !== '') {
            $innerQuery->where('name', 'like', $this->likePattern($search));
        }

        // Outer query: wraps inner as a derived table so distance_km is a real
        // column. This allows cursor pagination to reference distance_km in WHERE
        // clauses on page 2+. withoutGlobalScopes() prevents duplicate soft-delete
        // conditions on the outer query.
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

    /**
     * Compute a rectangular bounding box around a geographic point.
     *
     * Uses degree-per-km relationships:
     *   – 1° of latitude  ≈ 111.32 km  (constant everywhere)
     *   – 1° of longitude ≈ 111.32 × cos(lat) km  (shrinks toward the poles)
     *
     * The box is a conservative over-approximation of the true circle.
     * Rows inside the box but outside the exact radius are rejected by the
     * Haversine computed in the SELECT, so correctness is preserved.
     *
     * @param  float $latitude  Centre latitude  (-90 … 90)
     * @param  float $longitude Centre longitude (-180 … 180)
     * @param  float $radiusKm  Search radius in kilometres
     * @return array{min_lat: float, max_lat: float, min_lng: float, max_lng: float}
     */
    private function computeBoundingBox(float $latitude, float $longitude, float $radiusKm): array
    {
        // Degrees of latitude per km is constant globally
        $latDelta = $radiusKm / 111.32;

        // Degrees of longitude per km shrinks near the poles; clamp cos() to a
        // minimum to avoid division-by-zero at extreme latitudes.
        $cosLat   = max(cos(deg2rad($latitude)), 1e-10);
        $lngDelta = $radiusKm / (111.32 * $cosLat);

        return [
            'min_lat' => $latitude  - $latDelta,
            'max_lat' => $latitude  + $latDelta,
            'min_lng' => $longitude - $lngDelta,
            'max_lng' => $longitude + $lngDelta,
        ];
    }
}
