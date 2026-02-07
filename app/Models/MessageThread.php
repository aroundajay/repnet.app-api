<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * MessageThread model for polymorphic conversations.
 * 
 * Can be attached to: Gym, PartnerRequest, WorkoutVideo.
 * Groups messages into conversation threads.
 */
class MessageThread extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'messageable_type',
        'messageable_id',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the owning messageable model (Gym, PartnerRequest, WorkoutVideo).
     */
    public function messageable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get all messages in this thread.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'thread_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Get the latest message in this thread.
     */
    public function latestMessage(): ?Message
    {
        return $this->messages()->latest()->first();
    }

    /**
     * Get the message count.
     */
    public function messageCount(): int
    {
        return $this->messages()->count();
    }
}
