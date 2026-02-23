<?php

namespace App\Actions\Gym;

use App\Http\Requests\Gym\ListGymUsersRequest;
use App\Services\GymService;
use Illuminate\Http\JsonResponse;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * List Gym Users Action
 *
 * Lists members of a specific gym with cursor pagination (50 per page by default).
 * Supports:
 *   - ?q=<string>   – filter by user name or email (partial, case-insensitive)
 *   - ?status=<str> – filter by gym_users.status (pending|active|rejected)
 *   - ?cursor=<str> – opaque cursor string for next/previous page
 *   - ?per_page=<n> – override items per page (max 100)
 *
 * Flow: ListGymUsersRequest (validation) -> Action -> GymService -> GymUserRepository.
 */
class ListGymUsersAction
{
    use AsAction;

    public function __construct(
        protected GymService $gymService
    ) {}

    public function authorize(ListGymUsersRequest $request): bool
    {
        $gymUser = $this->gymService->findGymUserByGymIdAndUserId($request->route('gymId'), auth()->user()->id);

        if (!$gymUser) {
            return false;
        }

        return user_can('view member list', $gymUser->role);
    }

    /**
     * Handle the action as an HTTP controller.
     * Called when the action is bound to a route; reads the validated ListGymUsersRequest.
     *
     * @param ListGymUsersRequest $request Validated request
     * @return array Response array passed to jsonResponse()
     */
    public function asController(ListGymUsersRequest $request): array
    {
        return $this->handle(
            gymId: $request->route('gymId'),
            data: $request->validated(),
        );
    }

    /**
     * List gym members for the given gym with cursor pagination.
     *
     * @param string $gymId UUID of the target gym
     * @param array  $data  Validated params: q?, status?, per_page?, cursor?
     * @return array{success: bool, message: string, status_code: int, data: array}
     */
    public function handle(string $gymId, array $data): array
    {
        $paginator = $this->gymService->listUsers($gymId, $data);

        return [
            'success'     => true,
            'message'     => 'Gym users fetched successfully',
            'status_code' => 200,
            'data'        => [
                'gym_users'  => $paginator->items(),
                'pagination' => [
                    'per_page'    => $paginator->perPage(),
                    'next_cursor' => $paginator->nextCursor()?->encode(),
                    'prev_cursor' => $paginator->previousCursor()?->encode(),
                    'has_more'    => $paginator->hasMorePages(),
                ],
            ],
        ];
    }

    /**
     * Build a JSON response from the action result.
     */
    public function jsonResponse(array $data): JsonResponse
    {
        return response()->json($data, $data['status_code'] ?? 200);
    }
}
