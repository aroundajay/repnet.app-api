<?php

namespace App\Actions\Gym;

use App\Http\Requests\Gym\CreateGymRequest;
use App\Services\GymService;
use Illuminate\Http\JsonResponse;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Create Gym Action
 *
 * Creates a new gym with the current authenticated user as owner.
 * Flow: Form Request (validation) -> Action -> GymService -> Repositories.
 */
class CreateGymAction
{
    use AsAction;

    public function __construct(
        protected GymService $gymService
    ) {}

    /**
     * Handle the action as an HTTP controller.
     * Called when the action is used as a route; uses validated CreateGymRequest.
     *
     * @param CreateGymRequest $request Validated request with gym data
     * @return array Response array for jsonResponse
     */
    public function asController(CreateGymRequest $request): array
    {
        return $this->handle(
            $request->validated(),
            $request->user()->id
        );
    }

    /**
     * Create a gym and assign the given user as owner.
     *
     * @param array $data Validated data: name, description?, location_lat, location_lng, is_public?
     * @param string $ownerUserId Authenticated user's ID
     * @return array{success: bool, message: string, status_code: int, data: array}
     */
    public function handle(array $data, string $ownerUserId): array
    {
        $gym = $this->gymService->create($data, $ownerUserId);

        return [
            'success' => true,
            'message' => 'Gym created successfully',
            'status_code' => 201,
            'data' => [
                'gym' => $gym,
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
