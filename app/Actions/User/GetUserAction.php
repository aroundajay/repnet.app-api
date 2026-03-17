<?php

namespace App\Actions\User;

use App\Http\Requests\User\GetUserRequest;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Get User Action
 *
 * Fetches a user by ID with their related gyms, files, and notifications.
 * Returns the same relation set as the authenticated /user endpoint.
 *
 * Flow: GetUserRequest (validation) -> Action -> UserService -> UserRepository
 */
class GetUserAction
{
    use AsAction;

    public function __construct(
        protected UserService $userService
    ) {}

    /**
     * Authorize the request.
     * Any authenticated user may look up another user's profile.
     *
     * @param  GetUserRequest $request
     * @return bool
     */
    public function authorize(GetUserRequest $request): bool
    {
        return $request->user() !== null;
    }

    /**
     * Handle the action as an HTTP controller.
     *
     * @param  GetUserRequest $request
     * @return array
     */
    public function asController(GetUserRequest $request): array
    {
        return $this->handle(
            userId: $request->route('userId'),
        );
    }

    /**
     * Fetch a user by their ID with all related data.
     *
     * @param  string $userId UUID of the target user
     * @return array{success: bool, message: string, status_code: int, data: array}
     */
    public function handle(string $userId): array
    {
        $user = $this->userService->findById($userId, [
            'files',
        ]);

        if (!$user) {
            return [
                'success'     => false,
                'message'     => 'User not found',
                'status_code' => 404,
                'data'        => [],
            ];
        }

        return [
            'success'     => true,
            'message'     => 'User fetched successfully',
            'status_code' => 200,
            'data'        => $user,
        ];
    }

    /**
     * Build JSON response from action result.
     *
     * @param  array $data
     * @return JsonResponse
     */
    public function jsonResponse(array $data): JsonResponse
    {
        return response()->json($data, $data['status_code'] ?? 200);
    }
}
