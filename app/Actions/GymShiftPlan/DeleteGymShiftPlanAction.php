<?php

namespace App\Actions\GymShiftPlan;

use Lorisleiva\Actions\Concerns\AsAction;
use Lorisleiva\Actions\ActionRequest;
use App\Services\GymService;
use App\Services\GymShiftPlanService;
use Illuminate\Http\JsonResponse;

class DeleteGymShiftPlanAction
{
    use AsAction;

    public function __construct(
        protected GymService $gymService,
        protected GymShiftPlanService $gymShiftPlanService
    ) {}

    public function authorize(ActionRequest $request): bool
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

    public function handle(string $planId): array
    {
        $this->gymShiftPlanService->delete($planId);

        return [
            'success' => true,
            'message' => 'Gym shift plan deleted successfully',
            'status_code' => 200,
            'data' => null,
        ];
    }

    public function asController(ActionRequest $request): array
    {
        return $this->handle($request->route('planId'));
    }

    public function jsonResponse(array $data): JsonResponse
    {
        return response()->json($data, $data['status_code'] ?? 200);
    }
}
