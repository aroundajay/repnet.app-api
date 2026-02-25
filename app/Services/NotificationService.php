<?php

namespace App\Services;

use App\Models\Notification;
use App\Repositories\NotificationRepository;
use Illuminate\Contracts\Pagination\CursorPaginator;

class NotificationService
{
    public function __construct(
        protected NotificationRepository $notificationRepository
    ) {}

    public function create(array $data): Notification
    {
        return $this->notificationRepository->create($data);
    }

    public function list(string $userId, array $data): CursorPaginator
    {
        $perPage = (int) ($data['per_page'] ?? 15);
        $unreadOnly = isset($data['unread_only']) && filter_var($data['unread_only'], FILTER_VALIDATE_BOOLEAN);

        return $this->notificationRepository->listByUser($userId, $perPage, $unreadOnly);
    }

    public function markAsRead(string $userId, string $notificationId): bool
    {
        $notification = $this->notificationRepository->findByUserAndId($userId, $notificationId);
        if (!$notification) {
            return false;
        }

        return $this->notificationRepository->markAsRead($notification);
    }

    public function markAllAsRead(string $userId): int
    {
        return $this->notificationRepository->markAllAsRead($userId);
    }
}
