<?php

namespace App\Actions\User;

use App\Http\Requests\User\GetUserPostsRequest;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Get User Posts Action
 *
 * Returns a cursor-paginated list of public POST messages sent by a given user.
 *
 * Only messages where card_type = 'POST' and is_public = true are returned,
 * ordered chronologically (newest first).
 *
 * Response format mirrors UserFeedAction exactly.
 *
 * Flow: GetUserPostsRequest → GetUserPostsAction → UserPostsService → UserPostsRepository
 */
class GetUserPostsAction
{
    use AsAction;

    public function __construct(
        protected UserService $userService
    ) {}

    /**
     * Handle the action as an HTTP controller.
     *
     * @param  GetUserPostsRequest $request Validated request with pagination params
     * @return array                        Response array consumed by jsonResponse()
     */
    public function asController(GetUserPostsRequest $request): array
    {
        return $this->handle(
            userId: $request->route('userId'),
            data: $request->validated(),
        );
    }

    /**
     * Fetch a cursor-paginated list of public posts for the given user.
     *
     * @param  string $userId UUID of the target user
     * @param  array  $data   Validated params: per_page?, cursor?
     * @return array{success: bool, message: string, status_code: int, data: array}
     */
    public function handle(string $userId, array $data = []): array
    {
        $paginator = $this->userService->getPublicPosts($userId, $data);

        return [
            'success'     => true,
            'message'     => 'User posts fetched successfully',
            'status_code' => 200,
            'data'        => [
                'messages'   => $paginator->items(),
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
