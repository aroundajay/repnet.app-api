<?php

namespace App\Repositories;

use App\Models\Amenity;
use Illuminate\Database\Eloquent\Collection;

/**
 * Amenity Repository
 *
 * Handles database operations for the Amenity model.
 */
class AmenityRepository
{
    /**
     * Get all amenities.
     *
     * @return Collection
     */
    public function all(): Collection
    {
        return Amenity::orderBy('name', 'asc')->get();
    }
}
