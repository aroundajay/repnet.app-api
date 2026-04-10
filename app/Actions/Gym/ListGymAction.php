<?php

namespace App\Actions\Gym;

use App\Http\Requests\Gym\ListGymRequest;
use App\Services\GymService;
use Illuminate\Http\JsonResponse;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * List Gym Action
 *
 * Lists public gyms with optional search by name (q) and cursor pagination.
 * When latitude and longitude are provided, gyms are sorted by distance (ascending).
 * Flow: ListGymRequest (validation) -> Action -> GymService -> GymRepository.
 */
class ListGymAction
{
    use AsAction;

    public function __construct(
        protected GymService $gymService
    ) {}

    /**
     * Handle the action as an HTTP controller.
     * Called when the action is used as a route; uses validated ListGymRequest.
     *
     * @param ListGymRequest $request Validated request with optional lat/lng
     * @return array Response array for jsonResponse
     */
    public function asController(ListGymRequest $request): array
    {
        return $this->handle($request->validated());
    }

    /**
     * List public gyms with cursor pagination, optionally sorted by distance.
     *
     * @param array $data Validated data: latitude?, longitude?, per_page?, cursor?, q?
     * @return array{success: bool, message: string, status_code: int, data: array}
     */
    public function handle(array $data): array
    {
        $paginator = $this->gymService->list(array_merge(
            $data,
            [
                'with' => ['files', 'amenities', 'workoutTypes', 'gymShifts.gymShiftPlans']
            ]
        ));

        return [
            'success' => true,
            'message' => 'Gyms fetched successfully',
            'status_code' => 200,
            'data' => [
                'gyms' => $paginator->items(),
                'pagination' => [
                    'per_page' => $paginator->perPage(),
                    'next_cursor' => $paginator->nextCursor()?->encode(),
                    'prev_cursor' => $paginator->previousCursor()?->encode(),
                    'has_more' => $paginator->hasMorePages(),
                ],
            ],
        ];
    }

    /**
     * Build JSON response from action result.
     */
    public function jsonResponse(array $data): JsonResponse
    {
        return response()->json($data, $data['status_code'] ?? 200);
    }
}
