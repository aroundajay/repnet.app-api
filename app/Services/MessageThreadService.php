<?php

namespace App\Services;

use App\Models\MessageThread;
use App\Repositories\MessageThreadRepository;
use Illuminate\Contracts\Pagination\CursorPaginator;

/**
 * MessageThread Service
 *
 * Handles message-thread business logic:
 * - Creating a thread linked to a polymorphic messageable entity.
 * - Finding/resolving a thread by messageable entity or direct ID.
 * - Enabling/disabling threads (via disabled_at timestamp).
 */
class MessageThreadService
{
    public function __construct(
        protected MessageThreadRepository $messageThreadRepository
    ) {}

    /**
     * Create a new message thread for a given messageable entity.
     *
     * @param  string        $messageableType Fully-qualified class name (e.g. App\Models\Gym)
     * @param  string        $messageableId   UUID of the owning entity
     * @return MessageThread                  The created thread
     */
    public function create(string $messageableType, string $messageableId): MessageThread
    {
        return $this->messageThreadRepository->create([
            'messageable_type' => $messageableType,
            'messageable_id'   => $messageableId,
            'disabled_at'      => null,
        ]);
    }

    /**
     * Find a thread by its primary key.
     *
     * @param  string            $threadId Thread UUID
     * @param  array             $with     Eager-load relations
     * @return MessageThread|null
     */
    public function findById(string $threadId, array $with = []): ?MessageThread
    {
        return $this->messageThreadRepository->findById($threadId, $with);
    }

    /**
     * Resolve the thread that belongs to a given messageable entity.
     *
     * Returns null when no thread exists for that entity yet.
     *
     * @param  string            $messageableType Fully-qualified class name
     * @param  string            $messageableId   UUID of the owning entity
     * @param  array             $with            Eager-load relations
     * @return MessageThread|null
     */
    public function findByMessageable(string $messageableType, string $messageableId, array $with = []): ?MessageThread
    {
        return $this->messageThreadRepository->findByMessageable($messageableType, $messageableId, $with);
    }

    /**
     * List all threads owned by a given messageable type with cursor pagination.
     *
     * @param  string          $messageableType Fully-qualified class name
     * @param  array           $data            Optional: per_page
     * @param  array           $with            Eager-load relations
     * @return CursorPaginator
     */
    public function list(string $messageableType, array $data = [], array $with = []): CursorPaginator
    {
        $perPage = (int) ($data['per_page'] ?? 20);

        return $this->messageThreadRepository->listByMessageable($messageableType, $perPage, $with);
    }

    /**
     * Disable a thread, preventing new messages from being sent.
     *
     * Sets disabled_at to the current timestamp; idempotent if already disabled.
     *
     * @param  string        $threadId Thread UUID
     * @return MessageThread           The updated thread
     */
    public function disable(string $threadId): MessageThread
    {
        return $this->messageThreadRepository->update($threadId, [
            'disabled_at' => now(),
        ]);
    }

    /**
     * Re-enable a previously disabled thread.
     *
     * Clears disabled_at; idempotent if already active.
     *
     * @param  string        $threadId Thread UUID
     * @return MessageThread           The updated thread
     */
    public function enable(string $threadId): MessageThread
    {
        return $this->messageThreadRepository->update($threadId, [
            'disabled_at' => null,
        ]);
    }

    /**
     * Check whether a thread is currently active (not disabled).
     *
     * @param  MessageThread $thread
     * @return bool
     */
    public function isActive(MessageThread $thread): bool
    {
        return $thread->disabled_at === null;
    }
}
