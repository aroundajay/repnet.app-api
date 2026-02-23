<?php

namespace App\Repositories;

use App\Models\Notification;
use Illuminate\Contracts\Pagination\CursorPaginator;

class NotificationRepository
{
    public function create(array $data): Notification
    {
        return Notification::create($data);
    }

    public function listByUser(string $userId, int $perPage = 15, bool $unreadOnly = false): CursorPaginator
    {
        $query = Notification::where('user_id', $userId);
        
        if ($unreadOnly) {
            $query->whereNull('read_at');
        }

        return $query->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->cursorPaginate($perPage);
    }

    public function findByUserAndId(string $userId, string $id): ?Notification
    {
        return Notification::where('user_id', $userId)
            ->where('id', $id)
            ->first();
    }

    public function markAsRead(Notification $notification): bool
    {
        return $notification->update(['read_at' => now()]);
    }

    public function markAllAsRead(string $userId): int
    {
        return Notification::where('user_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
