<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;

/**
 * Trait HandlesMessageThread
 * 
 * Provides service operations to manage message thread counts.
 */
trait HandlesMessageThread
{
    /**
     * Increment the message count for a thread.
     *
     * @param string $threadId UUID of the message thread
     * @return void
     */
    public function incrementMessageCount(string $threadId): void
    {
        $repository = $this->getMessageRepository();
        $repository->incrementMessageCount($threadId);
    }

    /**
     * Decrement the message count for a thread.
     *
     * @param string $threadId UUID of the message thread
     * @return void
     */
    public function decrementMessageCount(string $threadId): void
    {
        $repository = $this->getMessageRepository();
        $repository->decrementMessageCount($threadId);
    }

    /**
     * Rebuild the message count cache for a model from the database.
     *
     * @param Model $model The model that has a messageThread relationship
     * @return void
     */
    public function rebuildMessageCountCache(Model $model): void
    {
        $repository = $this->getMessageRepository();
        $repository->rebuildMessageCountCache($model);
    }

    /**
     * Clear the message count cache for a thread.
     * Called when a thread is deleted so the stale Redis key is removed.
     *
     * @param string $threadId UUID of the deleted message thread
     * @return void
     */
    public function clearMessageCountCache(string $threadId): void
    {
        $repository = $this->getMessageRepository();
        $repository->clearMessageCountCache($threadId);
    }

    /**
     * Get the repository for message thread operations.
     * Classes using this trait must implement this method and return the repository instance.
     *
     * @return mixed
     */
    abstract protected function getMessageRepository();
}
