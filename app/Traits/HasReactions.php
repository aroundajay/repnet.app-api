<?php

namespace App\Traits;

use App\Models\Reaction;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Redis;
/**
 * Trait HasReactions
 * 
 * Provides model operations for handling user reactions and managing 
 * cache states via Redis.
 */
trait HasReactions
{
    /**
     * Get the available reaction types.
     */
    public static function getReactionTypes(): array
    {
        return [
            'LIKE', 'LAUGH', 'WOW', 'SAD', 'CELEBRATE', 'CLAP', 
            'FIST_BUMP', 'FLEX', 'HIGH_FIVE', 'PRAY', 'SMIRK', 
            'TEAR', 'WINK', 'FOLLOW'
        ];
    }

    /**
     * Initialize the HasReactions trait.
     * This method is automatically called by Eloquent when the model is instantiated.
     */
    public function initializeHasReactions(): void
    {
        $this->append(['is_reacted', 'reaction_meta']);
    }

    /**
     * Determine if the logged-in user has reacted to the model.
     *
     * @return Attribute<bool, never>
     */
    protected function isReacted(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!auth()->check()) {
                    return false;
                }
                
                $userId = auth()->user()->id;
                $table = $this->getTable();
                $id = $this->getKey();
                
                foreach (self::getReactionTypes() as $type) {
                    if (Redis::sismember("{$table}:{$id}:{$type}", $userId)) {
                        return $type;
                    }
                }

                return null;
            }
        );
    }

    /**
     * Get the reaction counts and user presence matrix for this model.
     *
     * @return Attribute<array, never>
     */
    protected function reactionMeta(): Attribute
    {
        return Attribute::make(
            get: function () {
                $table = $this->getTable();
                $id = $this->getKey();
                $meta = [];
                
                foreach (self::getReactionTypes() as $type) {
                    $count = (int) Redis::get("{$table}:{$id}:{$type}_count");
                    if ($count > 0) {
                        $meta["{$type}_count"] = $count;
                    }
                }

                return $meta;
            }
        );
    }

    /**
     * Get all of the reactions for the model.
     *
     * @return MorphMany<Reaction, static>
     */
    public function reactions(): MorphMany
    {
        return $this->morphMany(Reaction::class, 'reactable');
    }
}
