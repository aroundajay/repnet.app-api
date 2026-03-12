<?php

namespace App\Actions\Message;

use App\Http\Requests\Message\ListMessageReactedUsersRequest;
use App\Services\MessageService;
use Illuminate\Http\JsonResponse;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * List Message Reacted Users Action
 *
 * Lists all users who reacted to a specific message, with cursor pagination.
 * Supports filtering by a specific reaction type using the `reaction` query parameter.
 */
class ListMessageReactedUsersAction
{
    use AsAction;

    public function __construct(
        protected MessageService $messageService
    ) {}

    /**
     * Authorize the action.
     * The FormRequest currently handles auth check, but we could add more checks here.
     */
    public function authorize(ListMessageReactedUsersRequest $request): bool
    {
        return $request->user() !== null;
    }

    /**
     * Handle the action as an HTTP controller.
     *
     * @param ListMessageReactedUsersRequest $request Validated request
     * @return array Response array passed to jsonResponse()
     */
    public function asController(ListMessageReactedUsersRequest $request): array
    {
        return $this->handle(
            messageId: $request->route('messageId'),
            data: $request->validated(),
        );
    }

    /**
     * List message reacted users.
     *
     * @param string $messageId UUID of the target message
     * @param array  $data      Validated params: reaction?, per_page?, cursor?
     * @return array{success: bool, message: string, status_code: int, data: array}
     */
    public function handle(string $messageId, array $data): array
    {
        $paginator = $this->messageService->listReactedUsers($messageId, $data);

        return [
            'success'     => true,
            'message'     => 'Reacted users fetched successfully',
            'status_code' => 200,
            'data'        => [
                'users'      => $paginator->items(),
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
