<?php

namespace App\Services;

use App\Models\Message;
use App\Models\MessageThread;
use App\Repositories\MessageRepository;
use App\Repositories\MessageThreadRepository;
use App\Traits\HandlesMessageThread;
use App\Traits\HandlesReactions;
use Illuminate\Contracts\Pagination\CursorPaginator;

/**
 * Message Service
 *
 * Handles message-related business logic:
 * - Creating a message within a thread and syncing file attachments.
 * - Listing messages in a thread with cursor pagination.
 */
class MessageService
{
    use HandlesReactions, HandlesMessageThread;

    public function __construct(
        protected MessageRepository $messageRepository,
        protected MessageThreadRepository $messageThreadRepository
    ) {}

    /**
     * Send a new message in a thread.
     *
     * Creates the message record (using polymorphic sender) and syncs
     * any attached file IDs via the morph-to-many relationship.
     *
     * @param  string  $threadId Thread UUID to post into
     * @param  string  $senderType Sender type (user or gym)
     * @param  string  $senderId Sender UUID
     * @param  array   $data     Validated data: message, files?
     * @return Message           The persisted message with files loaded
     */
    public function create(string $threadId, string $senderType, string $senderId, array $data): Message
    {
        $message = $this->messageRepository->create([
            'thread_id'   => $threadId,
            'sender_type' => $senderType,
            'sender_id'   => $senderId,
            'message'     => $data['message'],
            'is_public'   => $data['is_public'] ?? false,
            'location_lat' => $data['location_lat'] ?? null,
            'location_lng' => $data['location_lng'] ?? null,
            'card_type'   => $data['card_type'] ?? 'POST',
            'data'        => $data['data'] ?? null,
        ]);

        // Attach any provided file IDs via the polymorphic pivot table
        if (!empty($data['files'])) {
            $message->files()->sync($data['files']);
        }

        // Increment the cached message count for this thread
        $this->incrementMessageCount($threadId);

        return $message->fresh(['files', 'sender.files', 'gym', 'messageThread']);
    }

    /**
     * Find a single message by its ID.
     *
     * @param  string       $id   Message UUID
     * @param  array        $with Eager-load relations
     * @return Message|null
     */
    public function findById(string $id, array $with = []): ?Message
    {
        return $this->messageRepository->findById($id, $with);
    }

    /**
     * List messages in a thread with cursor pagination.
     *
     * @param  string          $threadId Thread UUID
     * @param  array           $data     Optional: per_page, cursor
     * @return CursorPaginator
     */
    public function list(string $threadId, array $data): CursorPaginator
    {
        $perPage = (int) ($data['per_page'] ?? 20);

        return $this->messageRepository->listByThread($threadId, $perPage, ['files', 'sender.files', 'gym', 'messageThread']);
    }

    /**
     * Delete a message and all of its nested threads and child messages recursively.
     *
     * Depth-first: child messages are fully removed before their parent thread
     * and the root message itself are removed.
     * After recursion, the parent thread's Redis count is decremented.
     *
     * @param  string $messageId UUID of the message to delete
     * @return void
     */
    public function delete(string $messageId): void
    {
        $message = $this->messageRepository->findById($messageId, ['messageThread.messages']);

        // Hold the parent thread ID so we can decrement its count after deletion
        $parentThreadId = $message->thread_id;

        $this->deleteRecursively($message);

        // The message is now gone — decrement its parent thread's cached count
        $this->decrementMessageCount($parentThreadId);
    }

    /**
     * Recursively soft-delete a message and everything nested beneath it.
     *
     * For each message:
     *   1. Recurse into all child messages inside its own messageThread (if any)
     *   2. Soft-delete that thread and clear its Redis key
     *   3. Soft-delete the message itself
     *
     * @param  Message $message The message to delete
     * @return void
     */
    private function deleteRecursively(Message $message): void
    {
        /** @var MessageThread|null $thread The thread this message owns (as messageable) */
        $thread = $message->messageThread;

        if ($thread) {
            // Recurse into every child message before removing the thread
            foreach ($thread->messages as $childMessage) {
                $this->deleteRecursively($childMessage);
            }

            // Remove the thread from the database
            $this->messageThreadRepository->delete($thread);

            // Remove the stale Redis count for this now-deleted thread
            $this->clearMessageCountCache($thread->id);
        }

        $this->messageRepository->delete($message);
    }

    /**
     * Get the repository corresponding to the model for reaction operations.
     */
    protected function getReactionRepository(): MessageRepository
    {
        return $this->messageRepository;
    }

    /**
     * Get the repository for message thread operations.
     */
    protected function getMessageRepository(): MessageRepository
    {
        return $this->messageRepository;
    }

    /**
     * List users who reacted to a message with cursor pagination.
     *
     * @param  string          $messageId Message UUID
     * @param  array           $data      Optional: reaction, per_page, cursor
     * @return CursorPaginator
     */
    public function listReactedUsers(string $messageId, array $data): CursorPaginator
    {
        $perPage = (int) ($data['per_page'] ?? 20);

        return $this->messageRepository->listReactedUsers($messageId, $perPage, $data['reaction'] ?? null);
    }
}
