<?php

namespace App\Traits;

use App\Models\MessageThread;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\Redis;

/**
 * Trait HasMessageThread
 * 
 * Provides model operations for handling message threads
 * and managing cache states via Redis.
 */
trait HasMessageThread
{
    /**
     * Initialize the HasMessageThread trait.
     * This method is automatically called by Eloquent when the model is instantiated.
     */
    public function initializeHasMessageThread(): void
    {
        $this->append(['message_thread_message_count']);
    }

    /**
     * Get the message thread for the model.
     *
     * @return MorphOne<MessageThread, static>
     */
    public function messageThread(): MorphOne
    {
        return $this->morphOne(MessageThread::class, 'messageable');
    }

    /**
     * Get the message count for the model from Redis cache.
     * Falls back to 0 when no thread exists or cache is empty.
     *
     * @return Attribute<int, never>
     */
    protected function messageThreadMessageCount(): Attribute
    {
        return Attribute::make(
            get: function () {
                $thread = $this->messageThread;

                if (!$thread) {
                    return 0;
                }

                return (int) (Redis::get("message_threads:{$thread->id}:message_count") ?? 0);
            }
        );
    }
}
