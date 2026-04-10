<?php

namespace App\Actions\GymShiftPlan;

use Lorisleiva\Actions\Concerns\AsAction;
use App\Http\Requests\GymShiftPlan\CreateGymShiftPlanRequest;
use App\Services\GymService;
use App\Services\GymShiftPlanService;
use Illuminate\Http\JsonResponse;

class CreateGymShiftPlanAction
{
    use AsAction;

    public function __construct(
        protected GymService $gymService,
        protected GymShiftPlanService $gymShiftPlanService
    ) {}

    public function authorize(CreateGymShiftPlanRequest $request): bool
    {
        $gymUser = $this->gymService->findGymUserByGymIdAndUserId($request->route('gymId'), $request->user()->id);

        if (!$gymUser) {
            return false;
        }

        if ($gymUser->role !== \App\Models\GymUser::ROLE_OWNER && $gymUser->role !== \App\Models\GymUser::ROLE_ADMIN) {
            return false;
        }

        if ($gymUser->status !== \App\Models\GymUser::STATUS_ACTIVE) {
            return false;
        }

        return true;
    }

    public function handle(string $shiftId, array $data, string $userId): array
    {
        $data['updated_by'] = $userId;
        $plan = $this->gymShiftPlanService->create($shiftId, $data);

        return [
            'success' => true,
            'message' => 'Gym shift plan created successfully',
            'status_code' => 201,
            'data' => [
                'gym_shift_plan' => $plan,
            ],
        ];
    }

    public function asController(CreateGymShiftPlanRequest $request): array
    {
        return $this->handle($request->route('shiftId'), $request->validated(), $request->user()->id);
    }

    public function jsonResponse(array $data): JsonResponse
    {
        return response()->json($data, $data['status_code'] ?? 200);
    }
}
