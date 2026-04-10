<?php

namespace App\Repositories;

use App\Models\GymShiftPlan;
use Illuminate\Contracts\Pagination\CursorPaginator;

class GymShiftPlanRepository
{
    public function create(array $data): GymShiftPlan
    {
        return GymShiftPlan::create($data);
    }

    public function update(string $id, array $data): GymShiftPlan
    {
        $plan = $this->findById($id);
        $plan->update($data);
        return $plan->fresh();
    }

    public function delete(string $id): bool
    {
        $plan = $this->findById($id);
        return $plan->delete();
    }

    public function findById(string $id, array $with = []): ?GymShiftPlan
    {
        return GymShiftPlan::with($with)->findOrFail($id);
    }

    public function listByGymShiftId(string $gymShiftId, int $perPage = 50): CursorPaginator
    {
        return GymShiftPlan::where('gym_shift_id', $gymShiftId)
            ->orderBy('duration_minutes')
            ->orderBy('id')
            ->cursorPaginate($perPage);
    }

    public function markAsInactive(string $gymShiftId, int $durationMinutes, bool $personalTrainingEnabled): void
    {
        GymShiftPlan::where('gym_shift_id', $gymShiftId)
            ->where('duration_minutes', $durationMinutes)
            ->where('personal_training_enabled', $personalTrainingEnabled)->delete();
    }
}
