<?php

namespace App\Actions\GymShiftPlan;

use Lorisleiva\Actions\Concerns\AsAction;
use App\Http\Requests\GymShiftPlan\ListGymShiftPlanRequest;
use App\Services\GymShiftPlanService;
use Illuminate\Http\JsonResponse;

class ListGymShiftPlanAction
{
    use AsAction;

    public function __construct(
        protected GymShiftPlanService $gymShiftPlanService
    ) {}

    public function authorize(ListGymShiftPlanRequest $request): bool
    {
        return true;
    }

    public function handle(string $shiftId, array $data): array
    {
        $paginator = $this->gymShiftPlanService->listByGymShiftId($shiftId, $data);

        return [
            'success' => true,
            'message' => 'Gym shift plans fetched successfully',
            'status_code' => 200,
            'data' => [
                'gym_shift_plans' => $paginator->items(),
                'pagination' => [
                    'per_page' => $paginator->perPage(),
                    'next_cursor' => $paginator->nextCursor()?->encode(),
                    'prev_cursor' => $paginator->previousCursor()?->encode(),
                    'has_more' => $paginator->hasMorePages(),
                ],
            ],
        ];
    }

    public function asController(ListGymShiftPlanRequest $request): array
    {
        return $this->handle($request->route('shiftId'), $request->validated());
    }

    public function jsonResponse(array $data): JsonResponse
    {
        return response()->json($data, $data['status_code'] ?? 200);
    }
}
