<?php

namespace App\Actions\Message;

use App\Models\User;
use App\Services\MessageService;
use Illuminate\Http\JsonResponse;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsAction;
use App\Services\UserService;
use App\Events\NotificationCreated;

/**
 * Create Comment Message Action
 *
 * Sends a new comment message to a given message thread on behalf of the authenticated user.
 * Optional file attachments (by file ID) are synced via morph-to-many.
 *
 * Flow: CreateMessageRequest (validation) -> Action -> MessageService -> MessageRepository -> CreateMessageAction
 */
class CreateCommentMessageAction
{
    use AsAction;

    public function __construct(
        protected MessageService $messageService,
        protected UserService $userService,
    ) {}

    public function authorize(ActionRequest $request): bool
    {
        return $request->user() !== null;
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:10000'],

            // Optional file attachments - array of existing file UUIDs
            'files'   => ['nullable', 'array'],
            'files.*' => ['uuid', 'exists:files,id'],
        ];
    }

    /**
     * Handle the action as an HTTP controller.
     *
     * Resolves the thread ID from the route, pulls the authenticated user,
     * and delegates to handle().
     *
     * @param  ActionRequest $request Validated request containing message & files
     * @return array                         Response array for jsonResponse
     */
    public function asController(ActionRequest $request): array
    {
        return $this->handle(
            messageId: $request->route('messageId'),
            senderType: User::class,
            senderId: $request->user()->id,
            data: array_merge($request->validated(), [
                'card_type' => 'COMMENT',
                'is_public' => false,
            ]),
        );
    }

    /**
     * Create a new message in the given thread.
     *
     * @param  string $messageId UUID of the target message
     * @param  string $senderType Type of the sender (user or gym)
     * @param  string $senderId UUID of the sender
     * @param  array  $data     Validated payload: message (string), files? (array of UUIDs)
     * @return array{success: bool, message: string, status_code: int, data: array}
     */
    public function handle(string $messageId, string $senderType, string $senderId, array $data): array
    {
        $message = $this->messageService->findById($messageId, ['messageThread']);

        if (!$message) {
            return [
                'success'     => false,
                'message'     => 'Message not found',
                'status_code' => 404,
                'data'        => [],
            ];
        }

        if (!$message->messageThread) {
            // create message thread
            $message->messageThread()->create([
                'disabled_at' => null,
            ]);
        }

        $message = $message->fresh(['messageThread']);

        $response = CreateMessageAction::run($message->messageThread->id, $senderType, $senderId, $data);

        if ($response['success']) {
            $messageSender = $message->sender;
            $commentSender = $this->userService->findById($senderId);

            // dispatch the notification to the user
            NotificationCreated::dispatchIf(
                $messageSender->id !== $senderId,
                [
                    'channel' => 'push',
                    'user_id' => $messageSender->id,
                    'type' => 'message_comment',
                    'title' => $commentSender->name,
                    'body' => get_body_by_card_type_and_comment($message->card_type, $commentSender->name),
                    'icon_path' => $commentSender->profile_picture,
                    'action_url' => get_notification_action_url('message_comment', [
                        'message' => $message,
                    ]),
                    'data' => [
                        'message' => $message,
                    ],
                ]
            );
        }

        return $response;
    }

    /**
     * Build JSON response from action result.
     */
    public function jsonResponse(array $data): JsonResponse
    {
        return response()->json($data, $data['status_code'] ?? 200);
    }
}
