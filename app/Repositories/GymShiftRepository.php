<?php

namespace App\Repositories;

use App\Models\GymShift;
use Illuminate\Contracts\Pagination\CursorPaginator;

class GymShiftRepository
{
    public function create(array $data): GymShift
    {
        return GymShift::create($data);
    }

    public function update(string $id, array $data): GymShift
    {
        $shift = $this->findById($id);
        $shift->update($data);
        return $shift->fresh();
    }

    public function delete(string $id): bool
    {
        $shift = $this->findById($id);
        return $shift->delete();
    }

    public function findById(string $id, array $with = []): ?GymShift
    {
        return GymShift::with($with)->findOrFail($id);
    }

    public function listByGymId(string $gymId, int $perPage = 50): CursorPaginator
    {
        return GymShift::where('gym_id', $gymId)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->orderBy('id')
            ->cursorPaginate($perPage);
    }

    public function hasOverlappingShift(string $gymId, string $dayOfWeek, string $startTime, string $endTime, ?string $excludeShiftId = null): bool
    {
        $query = GymShift::where('gym_id', $gymId)
            ->where('day_of_week', $dayOfWeek)
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where('start_time', '<', $endTime)
                  ->where('end_time', '>', $startTime);
            });

        if ($excludeShiftId) {
            $query->where('id', '!=', $excludeShiftId);
        }

        return $query->exists();
    }
}
