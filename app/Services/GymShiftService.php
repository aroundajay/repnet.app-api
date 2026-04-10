<?php

namespace App\Services;

use App\Models\GymShift;
use App\Repositories\GymShiftRepository;
use Illuminate\Contracts\Pagination\CursorPaginator;

class GymShiftService
{
    public function __construct(
        protected GymShiftRepository $gymShiftRepository
    ) {}

    public function create(string $gymId, array $data): GymShift
    {
        $data['gym_id'] = $gymId;

        $this->ensureNoOverlap(
            $gymId,
            $data['day_of_week'],
            $data['start_time'],
            $data['end_time']
        );

        return $this->gymShiftRepository->create($data);
    }

    public function update(string $id, array $data): GymShift
    {
        if (isset($data['day_of_week']) || isset($data['start_time']) || isset($data['end_time'])) {
            $shift = $this->findById($id);

            $dayOfWeek = $data['day_of_week'] ?? $shift->day_of_week;
            $startTime = $data['start_time'] ?? $shift->start_time;
            $endTime = $data['end_time'] ?? $shift->end_time;

            $this->ensureNoOverlap(
                $shift->gym_id,
                $dayOfWeek,
                $startTime,
                $endTime,
                $id
            );
        }

        return $this->gymShiftRepository->update($id, $data);
    }

    public function delete(string $id): bool
    {
        return $this->gymShiftRepository->delete($id);
    }

    public function findById(string $id, array $with = []): ?GymShift
    {
        return $this->gymShiftRepository->findById($id, $with);
    }

    public function listByGymId(string $gymId, array $data): CursorPaginator
    {
        $perPage = (int) ($data['per_page'] ?? 50);
        return $this->gymShiftRepository->listByGymId($gymId, $perPage);
    }

    protected function ensureNoOverlap(string $gymId, string $dayOfWeek, string $startTime, string $endTime, ?string $excludeShiftId = null): void
    {
        if ($this->gymShiftRepository->hasOverlappingShift($gymId, $dayOfWeek, $startTime, $endTime, $excludeShiftId)) {
            throw new \Exception('The specified shift overlaps with an existing shift on this day.');
        }
    }
}
