<?php

namespace App\Services;

use App\Repositories\WorkoutTypeRepository;
use Illuminate\Database\Eloquent\Collection;

/**
 * WorkoutType Service
 *
 * Handles business logic for workout types.
 */
class WorkoutTypeService
{
    public function __construct(
        protected WorkoutTypeRepository $repository
    ) {}

    /**
     * List all workout types.
     *
     * @param array $with
     * @return Collection
     */
    public function listAll(array $with = []): Collection
    {
        return $this->repository->listAll($with);
    }
}
