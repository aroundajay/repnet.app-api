<?php

namespace App\Repositories;

use App\Models\WorkoutType;
use Illuminate\Database\Eloquent\Collection;

/**
 * WorkoutType Repository
 *
 * Handles database operations for WorkoutType model.
 */
class WorkoutTypeRepository
{
    /**
     * List all workout types.
     *
     * @param array $with
     * @return Collection
     */
    public function listAll(array $with = []): Collection
    {
        return WorkoutType::with($with)->get();
    }
}
