<?php

namespace App\Services;

use App\Repositories\AmenityRepository;
use Illuminate\Database\Eloquent\Collection;

/**
 * Amenity Service
 *
 * Handles business logic for amenities.
 */
class AmenityService
{
    public function __construct(
        protected AmenityRepository $amenityRepository
    ) {}

    /**
     * List all available amenities.
     *
     * @return Collection
     */
    public function list(): Collection
    {
        return $this->amenityRepository->all();
    }
}
