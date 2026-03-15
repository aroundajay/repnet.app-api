<?php

namespace App\Actions\Message;

use App\Http\Requests\Message\DeleteMessageRequest;
use App\Services\MessageService;
use Illuminate\Http\JsonResponse;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Delete Message Action
 *
 * Deletes a message, its owned messageThread, and all nested child messages
 * recursively. Only the original sender of the message may delete it.
 *
 * Flow: DeleteMessageRequest -> Action::authorize() -> Action::handle()
 *       -> MessageService::delete() -> recursive soft-deletes + Redis cleanup
 */
class DeleteMessageAction
{
    use AsAction;

    public function __construct(
        protected MessageService $messageService
    ) {}

    /**
     * Authorize the request.
     *
     * Only the sender of the message may delete it.
     * Returns 403 when the authenticated user does not own the message.
     *
     * @param  DeleteMessageRequest $request
     * @return bool
     */
    public function authorize(DeleteMessageRequest $request): bool
    {
        $message = $this->messageService->findById($request->route('messageId'));

        if (!$message) {
            return false;
        }

        // Only the original sender may delete their own message
        return $message->sender_id === $request->user()->id;
    }

    /**
     * Handle the action as an HTTP controller.
     *
     * @param  DeleteMessageRequest $request
     * @return array
     */
    public function asController(DeleteMessageRequest $request): array
    {
        return $this->handle(
            messageId: $request->route('messageId'),
        );
    }

    /**
     * Delete a message and all nested threads and child messages recursively.
     *
     * @param  string $messageId UUID of the message to delete
     * @return array{success: bool, message: string, status_code: int, data: array}
     */
    public function handle(string $messageId): array
    {
        $this->messageService->delete($messageId);

        return [
            'success'     => true,
            'message'     => 'Message deleted successfully',
            'status_code' => 200,
            'data'        => [],
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
