<?php

namespace App\Actions\Notification;

use App\Http\Requests\Notification\ListNotificationRequest;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Lorisleiva\Actions\Concerns\AsAction;

class ListNotificationAction
{
    use AsAction;

    public function __construct(
        protected NotificationService $notificationService
    ) {}

    public function asController(ListNotificationRequest $request): array
    {
        return $this->handle($request->user()->id, $request->validated());
    }

    public function handle(string $userId, array $data): array
    {
        $paginator = $this->notificationService->list($userId, $data);

        return [
            'success'     => true,
            'message'     => 'Notifications fetched successfully',
            'status_code' => 200,
            'data'        => [
                'notifications' => $paginator->items(),
                'pagination'    => [
                    'per_page'    => $paginator->perPage(),
                    'next_cursor' => $paginator->nextCursor()?->encode(),
                    'prev_cursor' => $paginator->previousCursor()?->encode(),
                    'has_more'    => $paginator->hasMorePages(),
                ],
            ],
        ];
    }

    public function jsonResponse(array $data): JsonResponse
    {
        return response()->json($data, $data['status_code'] ?? 200);
    }
}
