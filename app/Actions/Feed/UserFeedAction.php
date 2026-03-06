<?php

namespace App\Actions\Feed;

use App\Http\Requests\Feed\UserFeedRequest;
use App\Services\UserFeedService;
use Illuminate\Http\JsonResponse;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * User Feed Action
 *
 * Returns a cursor-paginated feed of messages for the authenticated user.
 *
 * Feed composition (sources merged + deduplicated):
 *   – All messages from every active gym-thread the user belongs to.
 *   – All public POST messages (card_type = 'POST', is_public = true).
 *
 * When latitude and longitude are provided the results are ordered chronologically
 * (newest first) and then by distance (ascending) from the supplied coordinates.
 * Otherwise they are ordered purely chronologically.
 *
 * Flow: UserFeedRequest → UserFeedAction → UserFeedService → UserFeedRepository
 */
class UserFeedAction
{
    use AsAction;

    public function __construct(
        protected UserFeedService $userFeedService
    ) {}

    /**
     * Handle the action as an HTTP controller.
     *
     * @param  UserFeedRequest $request Validated request with lat/lng and pagination params
     * @return array                    Response array consumed by jsonResponse()
     */
    public function asController(UserFeedRequest $request): array
    {
        return $this->handle(
            userId: auth()->user()->id,
            data: $request->validated(),
        );
    }

    /**
     * Fetch a cursor-paginated feed for the given user.
     *
     * @param  string $userId Authenticated user's UUID
     * @param  array  $data   Validated params: latitude?, longitude?, per_page?, cursor?
     * @return array{success: bool, message: string, status_code: int, data: array}
     */
    public function handle(string $userId, array $data = []): array
    {
        $paginator = $this->userFeedService->getFeed($userId, $data);

        return [
            'success'     => true,
            'message'     => 'Feed fetched successfully',
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
