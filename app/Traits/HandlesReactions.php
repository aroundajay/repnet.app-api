<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;

/**
 * Trait HandlesReactions
 * 
 * Provides service operations to toggle user reactions.
 */
trait HandlesReactions
{
    /**
     * Toggle a reaction for a given model ID.
     *
     * @param  string $modelId
     * @param  string $userId
     * @param  string $reactionType
     * @return array{added: bool, model: Model}
     */
    public function toggleReaction(string $modelId, string $userId, string $reactionType): array
    {
        $repository = $this->getReactionRepository();
        $model = $repository->findById($modelId);

        $added = $repository->toggleReaction($model, $userId, $reactionType);

        return [
            'added' => $added,
            'model' => $model->fresh(['files', 'sender', 'gym'])->toArray(),
        ];
    }

    /**
     * Get the repository corresponding to the model for reaction operations.
     * Classes using this trait must implement this method and return the repository instance.
     *
     * @return mixed
     */
    abstract protected function getReactionRepository();
}
