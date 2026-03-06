<?php

namespace App\Actions\Message;

use App\Http\Requests\Message\CreateMessageRequest;
use App\Models\Gym;
use App\Models\User;
use App\Services\MessageService;
use App\Services\MessageThreadService;
use Illuminate\Http\JsonResponse;
use Lorisleiva\Actions\Concerns\AsAction;


/**
 * Create Message Action
 *
 * Sends a new message to a given thread on behalf of the authenticated user.
 * Optional file attachments (by file ID) are synced via morph-to-many.
 *
 * Flow: CreateMessageRequest (validation) -> Action -> MessageService -> MessageRepository
 */
class CreateMessageAction
{
    use AsAction;

    public function __construct(
        protected MessageService $messageService,
        protected MessageThreadService $messageThreadService
    ) {}

    public function authorize(CreateMessageRequest $request): bool
    {
        $threadId = $request->route('threadId');

        $thread = $this->messageThreadService->findById($threadId);

        if (!$thread || !$this->messageThreadService->isActive($thread)) {
            return false;
        }

        if ($thread->messageable_type === Gym::class) {
            $gym = $thread->messageable;

            $isMember = $gym->gymUsers()->where('user_id', $request->user()->id)->exists();

            if (!$isMember) {
                return false;
            }

            $request->merge([
                'location_lat' => $gym->location_lat,
                'location_lng' => $gym->location_lng,
            ]);

            return true;
        }

        // @todo: handle partner request messages channel

        return true;
    }

    /**
     * Handle the action as an HTTP controller.
     *
     * Resolves the thread ID from the route, pulls the authenticated user,
     * and delegates to handle().
     *
     * @param  CreateMessageRequest $request Validated request containing message & files
     * @return array                         Response array for jsonResponse
     */
    public function asController(CreateMessageRequest $request): array
    {
        return $this->handle(
            threadId: $request->route('threadId'),
            senderType: User::class,
            senderId: $request->user()->id,
            data: array_merge($request->validated(), [
                'card_type' => 'POST',
                'location_lat' => $request->input('location_lat'),
                'location_lng' => $request->input('location_lng'),
            ]),
        );
    }

    /**
     * Create a new message in the given thread.
     *
     * @param  string $threadId UUID of the target MessageThread
     * @param  string $senderType Type of the sender (user or gym)
     * @param  string $senderId UUID of the sender
     * @param  array  $data     Validated payload: message (string), files? (array of UUIDs)
     * @return array{success: bool, message: string, status_code: int, data: array}
     */
    public function handle(string $threadId, string $senderType, string $senderId, array $data): array
    {
        $message = $this->messageService->create($threadId, $senderType, $senderId, $data);

        return [
            'success'     => true,
            'message'     => 'Message sent successfully',
            'status_code' => 201,
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
