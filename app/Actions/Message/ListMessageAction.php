<?php

namespace App\Actions\Message;

use App\Http\Requests\Message\ListMessageRequest;
use App\Models\Gym;
use App\Services\MessageService;
use App\Services\MessageThreadService;
use Illuminate\Http\JsonResponse;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * List Message Action
 *
 * Returns a cursor-paginated list of messages for a given thread,
 * sorted by created_at DESC (newest first).
 *
 * Authorization enforces:
 *   - Thread must exist and be active (not disabled).
 *   - For gym threads: caller must be a member of that gym.
 *
 * Flow: ListMessageRequest (validation) -> Action -> MessageService -> MessageRepository
 */
class ListMessageAction
{
    use AsAction;

    public function __construct(
        protected MessageService $messageService,
        protected MessageThreadService $messageThreadService
    ) {}

    /**
     * Authorize the request.
     *
     * Mirrors CreateMessageAction::authorize() for consistent access control:
     * - Rejects disabled threads.
     * - For gym-owned threads: caller must be an active gym member.
     */
    public function authorize(ListMessageRequest $request): bool
    {
        $threadId = $request->route('threadId');

        $thread = $this->messageThreadService->findById($threadId, ['messageable']);

        if (!$thread || !$this->messageThreadService->isActive($thread)) {
            return false;
        }

        if ($thread->messageable_type === Gym::class) {
            $gym = $thread->messageable;
            return $gym->gymUsers()->where('user_id', $request->user()->id)->exists();
        }

        // @todo: handle partner request messages channel

        return true;
    }

    /**
     * Handle the action as an HTTP controller.
     *
     * @param  ListMessageRequest $request Validated request with pagination params
     * @return array                        Response array for jsonResponse
     */
    public function asController(ListMessageRequest $request): array
    {
        return $this->handle(
            threadId: $request->route('threadId'),
            data: $request->validated(),
        );
    }

    /**
     * Fetch a cursor-paginated page of messages for the given thread.
     *
     * @param  string $threadId UUID of the target MessageThread
     * @param  array  $data     Validated params: per_page?, cursor?
     * @return array{success: bool, message: string, status_code: int, data: array}
     */
    public function handle(string $threadId, array $data = []): array
    {
        $paginator = $this->messageService->list($threadId, $data);

        return [
            'success'     => true,
            'message'     => 'Messages fetched successfully',
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
     * Build JSON response from action result.
     */
    public function jsonResponse(array $data): JsonResponse
    {
        return response()->json($data, $data['status_code'] ?? 200);
    }
}
