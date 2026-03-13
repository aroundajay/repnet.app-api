<?php

namespace App\Services;

use App\Repositories\UserFeedRepository;
use Illuminate\Contracts\Pagination\CursorPaginator;

/**
 * User Feed Service
 *
 * Orchestrates feed retrieval: resolves pagination parameters,
 * coordinates, and search radius, then delegates to UserFeedRepository.
 */
class UserFeedService
{
    /**
     * Maximum radius the caller is allowed to request in kilometres.
     * Prevents malicious large-radius requests from defeating the bounding-box
     * optimisation and scanning the whole table.
     */
    private const MAX_RADIUS_KM = 500.0;

    /**
     * Default radius when the caller omits the parameter.
     * Mirrors UserFeedRepository::DEFAULT_RADIUS_KM.
     */
    private const DEFAULT_RADIUS_KM = 50.0;

    public function __construct(
        protected UserFeedRepository $userFeedRepository
    ) {}

    /**
     * Build a cursor-paginated user feed.
     *
     * @param  string $userId Authenticated user's UUID
     * @param  array  $data   Validated request data:
     *                        latitude?, longitude?, radius_km?, per_page?, cursor?
     * @return CursorPaginator
     */
    public function getFeed(string $userId, array $data): CursorPaginator
    {
        $perPage   = (int)   ($data['per_page']  ?? 20);
        $latitude  = isset($data['latitude'])  ? (float) $data['latitude']  : null;
        $longitude = isset($data['longitude']) ? (float) $data['longitude'] : null;

        // Clamp radius to the allowed maximum so callers cannot bypass the
        // bounding-box optimisation by requesting an enormous radius.
        $radiusKm = (float) ($data['radius_km'] ?? self::DEFAULT_RADIUS_KM);
        $radiusKm = min($radiusKm, self::MAX_RADIUS_KM);

        return $this->userFeedRepository->getFeed(
            userId: $userId,
            perPage: $perPage,
            latitude: $latitude,
            longitude: $longitude,
            radiusKm: $radiusKm,
            with: ['files', 'sender', 'gym', 'messageThread'],
        );
    }
}
