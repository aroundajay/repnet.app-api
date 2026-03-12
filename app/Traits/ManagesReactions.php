<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redis;

/**
 * Trait ManagesReactions
 * 
 * Provides repository operations to toggle user reactions and rebuild
 * the Redis caches.
 */
trait ManagesReactions
{
    /**
     * Rebuild the reaction cache for a model based on exact reactions.
     *
     * @param Model $model
     * @return void
     */
    public function rebuildReactionCache(Model $model): void
    {
        $table = $model->getTable();
        $id = $model->getKey();
        
        if (!method_exists($model, 'getReactionTypes')) {
            return;
        }

        // Clear all existing caches for this model
        foreach ($model::getReactionTypes() as $type) {
            Redis::del("{$table}:{$id}:{$type}");
            Redis::del("{$table}:{$id}:{$type}_count");
        }
        
        $reactions = $model->reactions()->get()->groupBy('reaction');
        
        foreach ($reactions as $type => $group) {
            $userIds = $group->pluck('user_id')->toArray();
            if (!empty($userIds)) {
                Redis::sadd("{$table}:{$id}:{$type}", ...$userIds);
                Redis::set("{$table}:{$id}:{$type}_count", count($userIds));
            }
        }
    }

    /**
     * Add a reaction to a model.
     *
     * @param Model $model
     * @param string $userId
     * @param string $reactionType
     * @return void
     */
    public function addReaction(Model $model, string $userId, string $reactionType): void
    {
        $model->reactions()->firstOrCreate([
            'user_id' => $userId,
            'reaction' => $reactionType,
        ]);

        $table = $model->getTable();
        $id = $model->getKey();
        
        $setKey = "{$table}:{$id}:{$reactionType}";
        $countKey = "{$table}:{$id}:{$reactionType}_count";
        
        Redis::sadd($setKey, $userId);
        Redis::set($countKey, Redis::scard($setKey));
    }

    /**
     * Remove a reaction from a model.
     *
     * @param Model $model
     * @param string $userId
     * @param string $reactionType
     * @return void
     */
    public function removeReaction(Model $model, string $userId, string $reactionType): void
    {
        $deleted = $model->reactions()
            ->where('user_id', $userId)
            ->where('reaction', $reactionType)
            ->delete();

        if ($deleted) {
            $table = $model->getTable();
            $id = $model->getKey();
            
            $setKey = "{$table}:{$id}:{$reactionType}";
            $countKey = "{$table}:{$id}:{$reactionType}_count";
            
            Redis::srem($setKey, $userId);
            Redis::set($countKey, Redis::scard($setKey));
        }
    }

    /**
     * Toggle a reaction on a model.
     *
     * @param Model $model
     * @param string $userId
     * @param string $reactionType
     * @return bool True if added, false if removed
     */
    public function toggleReaction(Model $model, string $userId, string $reactionType): bool
    {
        // Try to find an existing reaction by this user for this precise reaction type.
        $existing = $model->reactions()
            ->where('user_id', $userId)
            ->where('reaction', $reactionType)
            ->first();        

        // If it exists, remove it.
        if ($existing) {
            $this->removeReaction($model, $userId, $reactionType);
            return false;
        }

        // If it does not exist, add it.
        $this->addReaction($model, $userId, $reactionType);
        return true;
    }
}
