<?php

namespace App\Repositories;

use App\Models\Message;
use App\Traits\ManagesMessageThread;
use App\Traits\ManagesReactions;
use Illuminate\Contracts\Pagination\CursorPaginator;

/**
 * Message Repository
 *
 * Handles all database operations for the Message model.
 * Encapsulates data access so services stay free of query logic.
 */
class MessageRepository
{
    use ManagesReactions, ManagesMessageThread;
    /*
    |--------------------------------------------------------------------------
    | Create Operations
    |--------------------------------------------------------------------------
    */

    /**
     * Create a new message record.
     *
     * @param  array $data Message data: thread_id, sender_type, sender_id, message
     * @return Message     The created message model
     */
    public function create(array $data): Message
    {
        return Message::create($data);
    }

    /*
    |--------------------------------------------------------------------------
    | Read Operations
    |--------------------------------------------------------------------------
    */

    /**
     * Find a message by its ID.
     *
     * @param  string        $id   Message UUID
     * @param  array         $with Eager-load relations
     * @return Message|null
     */
    public function findById(string $id, array $with = []): ?Message
    {
        return Message::with($with)->findOrFail($id);
    }

    /**
     * List messages for a given thread with cursor pagination, newest first.
     *
     * @param  string          $threadId Thread UUID
     * @param  int             $perPage  Items per page
     * @param  array           $with     Eager-load relations
     * @return CursorPaginator
     */
    public function listByThread(string $threadId, int $perPage = 20, array $with = []): CursorPaginator
    {
        return Message::with($with)
            ->where('thread_id', $threadId)
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc') // deterministic tie-breaker for cursor pagination
            ->cursorPaginate($perPage);
    }

    /**
     * List users who reacted to a message with cursor pagination.
     *
     * @param string      $messageId Message UUID
     * @param int         $perPage   Items per page
     * @param string|null $reaction  Optional reaction type filter
     * @return CursorPaginator
     */
    public function listReactedUsers(string $messageId, int $perPage = 20, ?string $reaction = null): CursorPaginator
    {
        // Find the message
        $message = $this->findById($messageId);

        // Get the reactions relation query
        $query = $message->reactions()->with('user');

        if ($reaction) {
            $query->where('reaction', $reaction);
        }

        // Paginate over reactions to get cursor pagination, then map to users.
        return $query->orderBy('created_at', 'desc')
                     ->orderBy('id', 'desc')
                     ->cursorPaginate($perPage)
                     ->through(fn ($reactionModel) => $reactionModel->user);
    }
}
