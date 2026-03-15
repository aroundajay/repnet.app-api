<?php

namespace App\Repositories;

use App\Models\MessageThread;
use Illuminate\Contracts\Pagination\CursorPaginator;

/**
 * MessageThread Repository
 *
 * Handles all database operations for the MessageThread model.
 * Encapsulates data access so services stay free of query logic.
 */
class MessageThreadRepository
{
    /*
    |--------------------------------------------------------------------------
    | Create Operations
    |--------------------------------------------------------------------------
    */

    /**
     * Create a new message thread record.
     *
     * @param  array         $data Thread data: messageable_type, messageable_id, disabled_at?
     * @return MessageThread       The created thread
     */
    public function create(array $data): MessageThread
    {
        return MessageThread::create($data);
    }

    /*
    |--------------------------------------------------------------------------
    | Read Operations
    |--------------------------------------------------------------------------
    */

    /**
     * Find a thread by its primary key.
     *
     * @param  string            $id   MessageThread UUID
     * @param  array             $with Eager-load relations
     * @return MessageThread|null
     */
    public function findById(string $id, array $with = []): ?MessageThread
    {
        return MessageThread::with($with)->findOrFail($id);
    }

    /**
     * Find the thread owned by a specific messageable model.
     *
     * Useful for resolving the thread for a given Gym, PartnerRequest, etc.
     * Returns null when no thread has been created yet for that entity.
     *
     * @param  string            $messageableType Fully-qualified class name (e.g. App\Models\Gym)
     * @param  string            $messageableId   UUID of the owning entity
     * @param  array             $with            Eager-load relations
     * @return MessageThread|null
     */
    public function findByMessageable(string $messageableType, string $messageableId, array $with = []): ?MessageThread
    {
        return MessageThread::with($with)
            ->where('messageable_type', $messageableType)
            ->where('messageable_id', $messageableId)
            ->first();
    }

    /**
     * List all threads owned by a messageable type, with cursor pagination.
     *
     * @param  string          $messageableType Fully-qualified class name
     * @param  int             $perPage         Items per page
     * @param  array           $with            Eager-load relations
     * @return CursorPaginator
     */
    public function listByMessageable(string $messageableType, int $perPage = 20, array $with = []): CursorPaginator
    {
        return MessageThread::with($with)
            ->where('messageable_type', $messageableType)
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc') // deterministic tie-breaker for cursor pagination
            ->cursorPaginate($perPage);
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Operations
    |--------------------------------------------------------------------------
    */

    /**
     * Soft-delete a message thread record.
     *
     * @param  MessageThread $thread The thread to delete
     * @return void
     */
    public function delete(MessageThread $thread): void
    {
        $thread->delete();
    }

    /*
    |--------------------------------------------------------------------------
    | Update Operations
    |--------------------------------------------------------------------------
    */

    /**
     * Update a thread's attributes.
     *
     * @param  string        $id   Thread UUID
     * @param  array         $data Attributes to update
     * @return MessageThread       The updated, refreshed thread
     */
    public function update(string $id, array $data): MessageThread
    {
        $thread = $this->findById($id);
        $thread->update($data);

        return $thread->fresh();
    }
}
