<?php

namespace App\Services;

use App\Models\GymShiftPlan;
use App\Repositories\GymShiftPlanRepository;
use Illuminate\Contracts\Pagination\CursorPaginator;

class GymShiftPlanService
{
    public function __construct(
        protected GymShiftPlanRepository $gymShiftPlanRepository
    ) {}

    public function create(string $gymShiftId, array $data): GymShiftPlan
    {
        $data['gym_shift_id'] = $gymShiftId;
        $this->gymShiftPlanRepository->markAsInactive(
            $gymShiftId,
            $data['duration_minutes'],
            $data['personal_training_enabled']
        );

        return $this->gymShiftPlanRepository->create($data);
    }

    public function update(string $id, array $data): GymShiftPlan
    {
        return $this->gymShiftPlanRepository->update($id, $data);
    }

    public function delete(string $id): bool
    {
        return $this->gymShiftPlanRepository->delete($id);
    }

    public function findById(string $id, array $with = []): ?GymShiftPlan
    {
        return $this->gymShiftPlanRepository->findById($id, $with);
    }

    public function listByGymShiftId(string $gymShiftId, array $data): CursorPaginator
    {
        $perPage = (int) ($data['per_page'] ?? 50);
        return $this->gymShiftPlanRepository->listByGymShiftId($gymShiftId, $perPage);
    }
}
