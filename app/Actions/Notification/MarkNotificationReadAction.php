<?php

namespace App\Actions\Notification;

use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Lorisleiva\Actions\Concerns\AsAction;
use Lorisleiva\Actions\ActionRequest;

class MarkNotificationReadAction
{
    use AsAction;

    public function __construct(
        protected NotificationService $notificationService
    ) {}

    public function asController(ActionRequest $request): array
    {
        $notificationId = $request->route('notificationId');
        return $this->handle($request->user()->id, $notificationId);
    }

    public function handle(string $userId, string $notificationId): array
    {
        $success = $this->notificationService->markAsRead($userId, $notificationId);

        if (!$success) {
            return [
                'success' => false,
                'status_code' => 404,
                'message' => 'Notification not found or access denied',
            ];
        }

        return [
            'success'     => true,
            'status_code' => 200,
            'message'     => 'Notification marked as read successfully',
        ];
    }

    public function jsonResponse(array $data): JsonResponse
    {
        return response()->json($data, $data['status_code'] ?? 200);
    }
}
