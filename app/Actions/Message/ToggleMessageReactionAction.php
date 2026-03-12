<?php

namespace App\Actions\Message;

use App\Services\MessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Toggle Message Reaction Action
 *
 * Toggles a reaction on a message for the authenticated user.
 * Supports adding and removing standard reaction types.
 */
class ToggleMessageReactionAction
{
    use AsAction;

    public function __construct(
        protected MessageService $messageService
    ) {}

    /**
     * Define the validation rules for the action.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'reaction' => 'required|string|in:LIKE,LAUGH,WOW,SAD,CELEBRATE,CLAP,FIST_BUMP,FLEX,HIGH_FIVE,PRAY,SMIRK,TEAR,WINK,FOLLOW',
        ];
    }

    /**
     * Authorize the action.
     *
     * @param  Request $request
     * @return bool
     */
    public function authorize(Request $request): bool
    {
        return $request->user() !== null;
    }

    /**
     * Handle the action as an HTTP controller.
     *
     * @param  Request $request
     * @return array
     */
    public function asController(Request $request): array
    {
        $validated = $request->validate($this->rules());

        return $this->handle(
            messageId: $request->route('messageId'),
            userId: $request->user()->id,
            reactionType: $validated['reaction']
        );
    }

    /**
     * Execute the action.
     *
     * @param  string $messageId    UUID of the target Message
     * @param  string $userId       UUID of the user toggling reaction
     * @param  string $reactionType Type of the reaction
     * @return array{success: bool, message: string, status_code: int, data: array}
     */
    public function handle(string $messageId, string $userId, string $reactionType): array
    {
        $result = $this->messageService->toggleReaction($messageId, $userId, $reactionType);

        return [
            'success'     => true,
            'message'     => $result['added'] ? 'Reaction added successfully' : 'Reaction removed successfully',
            'status_code' => 200,
            'data'        => [
                'added'   => $result['added'],
                'model' => $result['model'],
            ],
        ];
    }

    /**
     * Build JSON response from action result.
     *
     * @param array $data
     * @return JsonResponse
     */
    public function jsonResponse(array $data): JsonResponse
    {
        return response()->json(Arr::only($data, [
            'success',
            'message',
            'status_code',
            'data',
        ]), $data['status_code'] ?? 200);
    }
}
