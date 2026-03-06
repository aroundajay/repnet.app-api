<?php

namespace App\Repositories;

use App\Models\Gym;
use App\Models\GymUser;
use App\Models\Message;
use Illuminate\Contracts\Pagination\CursorPaginator;

/**
 * User Feed Repository
 *
 * Builds an optimised, deduplicated feed of messages for an authenticated user.
 *
 * ┌──────────────────────────────────────────────────────────────────────────┐
 * │  Feed sources (combined via UNION – duplicates eliminated automatically) │
 * │                                                                          │
 * │  Leg A  All messages from every gym thread where the user holds an       │
 * │         active role (gym_users.status = 'active').                       │
 * │         Row set is already bounded by the user's gym count – no          │
 * │         geographic filter needed here.                                   │
 * │                                                                          │
 * │  Leg B  Public POST messages (card_type = 'POST', is_public = TRUE).     │
 * │         When lat/lng are supplied a BOUNDING BOX pre-filter is applied   │
 * │         BEFORE Haversine so only messages near the user are scanned.     │
 * │         Uses index: (card_type, is_public, location_lat, location_lng).  │
 * └──────────────────────────────────────────────────────────────────────────┘
 *
 * Ordering:
 *   – With coordinates  → created_at DESC, distance_km ASC
 *   – Without           → created_at DESC
 *
 * Cursor pagination is applied on the outer derived-table query so Laravel's
 * cursor mechanism can reference real column names in its WHERE clauses.
 */
class UserFeedRepository
{
    /**
     * Default search radius in kilometers when the caller does not supply one.
     * 50 km is a sensible default for a local gym/fitness-oriented feed.
     */
    private const DEFAULT_RADIUS_KM = 50.0;

    /**
     * Fetch a cursor-paginated user feed, optionally sorted by distance.
     *
     * @param  string     $userId    Authenticated user's UUID
     * @param  int        $perPage   Items per cursor page
     * @param  float|null $latitude  User latitude  (required for distance sort + bounding box)
     * @param  float|null $longitude User longitude (required for distance sort + bounding box)
     * @param  float      $radiusKm  Search radius for public posts bounding box (default 50 km)
     * @param  array      $with      Eloquent relations to eager-load on final results
     * @return CursorPaginator
     */
    public function getFeed(
        string $userId,
        int $perPage = 20,
        ?float $latitude = null,
        ?float $longitude = null,
        float $radiusKm = self::DEFAULT_RADIUS_KM,
        array $with = [],
    ): CursorPaginator {
        $gymClass = (new Gym)->getMorphClass();

        // ------------------------------------------------------------------ //
        // Leg A – messages from the gym threads the user is active in.        //
        //                                                                      //
        // Bounded by the user's own gym memberships, so the row count is      //
        // inherently small. No geographic filter required.                     //
        //                                                                      //
        // Joins: messages → message_threads → gyms → gym_users (user + active)//
        // ------------------------------------------------------------------ //
        $gymThreadMessages = Message::query()
            ->select('messages.*')
            ->join('message_threads', 'message_threads.id', '=', 'messages.thread_id')
            ->join('gyms', function ($join) use ($gymClass) {
                $join->on('gyms.id', '=', 'message_threads.messageable_id')
                     ->where('message_threads.messageable_type', '=', $gymClass);
            })
            ->join('gym_users', function ($join) use ($userId) {
                $join->on('gym_users.gym_id', '=', 'gyms.id')
                     ->where('gym_users.user_id', '=', $userId)
                     ->where('gym_users.status', '=', GymUser::STATUS_ACTIVE)
                     ->whereNull('gym_users.deleted_at');
            })
            ->whereNull('message_threads.deleted_at')
            ->whereNull('message_threads.disabled_at')
            ->whereNull('gyms.deleted_at')
            ->whereNull('messages.deleted_at');

        // ------------------------------------------------------------------ //
        // Leg B – public POST messages, optionally constrained to a bounding  //
        // box around the user's position.                                      //
        //                                                                      //
        // Without bounding box: full scan of (card_type = POST, is_public = 1)//
        // With bounding box:    index seek on (card_type, is_public,           //
        //                       location_lat, location_lng) then tiny range   //
        //                       scan – O(rows in bbox) instead of O(N_public).//
        // ------------------------------------------------------------------ //
        $publicPosts = Message::query()
            ->select('messages.*')
            ->where('messages.card_type', '=', 'POST')
            ->where('messages.is_public', '=', true)
            ->whereNull('messages.deleted_at');

        if ($latitude !== null && $longitude !== null) {
            $bbox = $this->computeBoundingBox($latitude, $longitude, $radiusKm);

            $publicPosts
                ->whereBetween('messages.location_lat', [$bbox['min_lat'], $bbox['max_lat']])
                ->whereBetween('messages.location_lng', [$bbox['min_lng'], $bbox['max_lng']]);
        }

        // ------------------------------------------------------------------ //
        // Combine – UNION deduplicates rows that appear in both legs          //
        // (a gym member's own posts that are also public).                    //
        // toBase() strips Eloquent so we get a raw query builder ready for    //
        // unioning; the outer query re-applies Eloquent hydration.            //
        // ------------------------------------------------------------------ //
        $unionQuery = $gymThreadMessages->toBase()->union($publicPosts->toBase());

        // ------------------------------------------------------------------ //
        // Outer query – wraps the UNION as a derived table so that any extra  //
        // computed column (distance_km) becomes a real column that Laravel's  //
        // cursor pagination can reference in its WHERE clauses on page 2+.    //
        // ------------------------------------------------------------------ //
        $outer = Message::withoutGlobalScopes()->fromSub($unionQuery, 'messages');

        if ($latitude !== null && $longitude !== null) {
            // Haversine formula – Earth radius 6 371 km
            $haversine = "
                6371 * acos(
                    cos(radians(?))
                    * cos(radians(messages.location_lat))
                    * cos(radians(messages.location_lng) - radians(?))
                    + sin(radians(?))
                    * sin(radians(messages.location_lat))
                )
            ";

            $outer
                ->selectRaw('messages.*')
                ->selectRaw("({$haversine}) AS distance_km", [$latitude, $longitude, $latitude])
                ->orderBy('created_at', 'desc')
                ->orderBy('distance_km', 'asc')
                ->orderBy('id', 'desc'); // deterministic tie-breaker for cursor pagination
        } else {
            $outer
                ->select('messages.*')
                ->orderBy('created_at', 'desc')
                ->orderBy('id', 'desc'); // deterministic tie-breaker for cursor pagination
        }

        if (!empty($with)) {
            $outer->with($with);
        }

        return $outer->cursorPaginate($perPage);
    }

    // ======================================================================= //
    // Private helpers                                                          //
    // ======================================================================= //

    /**
     * Compute a rectangular bounding box around a point.
     *
     * Uses degree-per-km relationships:
     *   – 1° of latitude  ≈ 111.32 km (constant across longitude)
     *   – 1° of longitude ≈ 111.32 × cos(lat) km  (shrinks toward the poles)
     *
     * The box is a conservative over-approximation of the true circle.  Rows
     * inside the box but outside the exact radius are still rejected by the
     * Haversine applied in the SELECT, so correctness is preserved.
     *
     * @param  float $latitude  Centre latitude  (-90 … 90)
     * @param  float $longitude Centre longitude (-180 … 180)
     * @param  float $radiusKm  Search radius in kilometres
     * @return array{min_lat: float, max_lat: float, min_lng: float, max_lng: float}
     */
    private function computeBoundingBox(float $latitude, float $longitude, float $radiusKm): array
    {
        // Degrees of latitude per km is constant
        $latDelta = $radiusKm / 111.32;

        // Degrees of longitude per km shrinks near the poles; clamp cos() to a
        // minimum to avoid division-by-zero at the poles (practically impossible
        // for a gym/fitness app but good practice).
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
