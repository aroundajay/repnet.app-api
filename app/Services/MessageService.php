<?php

namespace App\Services;

use App\Models\Message;
use App\Repositories\MessageRepository;
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
    public function __construct(
        protected MessageRepository $messageRepository
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
        ]);

        // Attach any provided file IDs via the polymorphic pivot table
        if (!empty($data['files'])) {
            $message->files()->sync($data['files']);
        }

        return $message->fresh(['files', 'sender']);
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

        return $this->messageRepository->listByThread($threadId, $perPage, ['files', 'sender']);
    }
}
