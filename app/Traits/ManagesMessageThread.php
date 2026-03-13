<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redis;

/**
 * Trait ManagesMessageThread
 * 
 * Provides repository operations to manage message thread counts
 * and rebuild the Redis caches.
 */
trait ManagesMessageThread
{
    /**
     * Rebuild the message count cache for a model from the database.
     * Reads the actual message count from the thread and stores it in Redis.
     *
     * @param Model $model The model that has a messageThread relationship
     * @return void
     */
    public function rebuildMessageCountCache(Model $model): void
    {
        if (!method_exists($model, 'messageThread')) {
            return;
        }

        $thread = $model->messageThread;

        if (!$thread) {
            return;
        }

        $count = $thread->messages()->count();
        Redis::set("message_threads:{$thread->id}:message_count", $count);
    }

    /**
     * Increment the message count for a thread in Redis.
     * Called after a new message is created in the thread.
     *
     * @param string $threadId UUID of the message thread
     * @return void
     */
    public function incrementMessageCount(string $threadId): void
    {
        Redis::incr("message_threads:{$threadId}:message_count");
    }

    /**
     * Decrement the message count for a thread in Redis.
     * Called after a message is deleted from the thread.
     * Ensures the count never goes below zero.
     *
     * @param string $threadId UUID of the message thread
     * @return void
     */
    public function decrementMessageCount(string $threadId): void
    {
        $key = "message_threads:{$threadId}:message_count";
        Redis::decr($key);

        // Ensure count never goes negative
        if ((int) Redis::get($key) < 0) {
            Redis::set($key, 0);
        }
    }
}
