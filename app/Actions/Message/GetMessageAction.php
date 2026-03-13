<?php

namespace App\Actions\Message;

use App\Http\Requests\Message\GetMessageRequest;
use App\Models\Gym;
use App\Services\MessageService;
use App\Services\MessageThreadService;
use Illuminate\Http\JsonResponse;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Get Message Action
 *
 * Returns a single message by its ID within a given thread.
 *
 * Authorization enforces:
 *   - Thread must exist and be active (not disabled).
 *   - For gym threads: caller must be a member of that gym.
 *
 * Flow: GetMessageRequest (validation) -> Action -> MessageService -> MessageRepository
 */
class GetMessageAction
{
    use AsAction;

    public function __construct(
        protected MessageService $messageService,
        protected MessageThreadService $messageThreadService
    ) {}

    /**
     * Authorize the request.
     *
     * - Rejects disabled threads.
     * - For gym-owned threads: caller must be an active gym member.
     */
    public function authorize(GetMessageRequest $request): bool
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
     * @param  GetMessageRequest $request Validated request
     * @return array                      Response array for jsonResponse
     */
    public function asController(GetMessageRequest $request): array
    {
        return $this->handle(
            messageId: $request->route('messageId'),
        );
    }

    /**
     * Fetch a single message by its ID.
     *
     * @param  string $messageId UUID of the target Message
     * @return array{success: bool, message: string, status_code: int, data: array}
     */
    public function handle(string $messageId): array
    {
        $message = $this->messageService->findById($messageId, ['files', 'sender', 'gym', 'messageThread']);

        return [
            'success'     => true,
            'message'     => 'Message fetched successfully',
            'status_code' => 200,
            'data'        => [
                'message' => $message,
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
