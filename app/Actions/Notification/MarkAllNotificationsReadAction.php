<?php

namespace App\Actions\Notification;

use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Lorisleiva\Actions\Concerns\AsAction;
use Lorisleiva\Actions\ActionRequest;

class MarkAllNotificationsReadAction
{
    use AsAction;

    public function __construct(
        protected NotificationService $notificationService
    ) {}

    public function asController(ActionRequest $request): array
    {
        return $this->handle($request->user()->id);
    }

    public function handle(string $userId): array
    {
        $count = $this->notificationService->markAllAsRead($userId);

        return [
            'success'     => true,
            'status_code' => 200,
            'message'     => "Successfully marked {$count} notifications as read",
        ];
    }

    public function jsonResponse(array $data): JsonResponse
    {
        return response()->json($data, $data['status_code'] ?? 200);
    }
}
