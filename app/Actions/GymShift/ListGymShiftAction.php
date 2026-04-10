<?php

namespace App\Actions\GymShift;

use Lorisleiva\Actions\Concerns\AsAction;
use App\Http\Requests\GymShift\ListGymShiftRequest;
use App\Services\GymShiftService;
use Illuminate\Http\JsonResponse;

class ListGymShiftAction
{
    use AsAction;

    public function __construct(
        protected GymShiftService $gymShiftService
    ) {}

    public function authorize(ListGymShiftRequest $request): bool
    {
        return true;
    }

    public function handle(string $gymId, array $data): array
    {
        $paginator = $this->gymShiftService->listByGymId($gymId, $data);

        return [
            'success' => true,
            'message' => 'Gym shifts fetched successfully',
            'status_code' => 200,
            'data' => [
                'gym_shifts' => $paginator->items(),
                'pagination' => [
                    'per_page' => $paginator->perPage(),
                    'next_cursor' => $paginator->nextCursor()?->encode(),
                    'prev_cursor' => $paginator->previousCursor()?->encode(),
                    'has_more' => $paginator->hasMorePages(),
                ],
            ],
        ];
    }

    public function asController(ListGymShiftRequest $request): array
    {
        return $this->handle($request->route('gymId'), $request->validated());
    }

    public function jsonResponse(array $data): JsonResponse
    {
        return response()->json($data, $data['status_code'] ?? 200);
    }
}
