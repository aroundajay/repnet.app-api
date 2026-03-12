<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphToMany; 
use Illuminate\Database\Eloquent\Relations\MorphTo;

use App\Traits\HasReactions;

/**
 * Message model for individual chat messages.
 * 
 * Belongs to a MessageThread for conversation grouping.
 */
class Message extends Model
{
    use HasFactory, HasUuids, SoftDeletes, HasReactions;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'thread_id',
        'sender_type',
        'sender_id',
        'message',
        'location_lat',
        'location_lng',
        'is_public',
        'card_type',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the thread this message belongs to.
     */
    public function thread(): BelongsTo
    {
        return $this->belongsTo(MessageThread::class, 'thread_id');
    }

    /**
     * Get the user who sent this message.
     */
    public function sender(): MorphTo
    {
        return $this->morphTo();
    }

    public function gym()
    {
        return $this->hasOneThrough(Gym::class, MessageThread::class, 'id', 'id', 'thread_id', 'messageable_id');
    }

    /**
     * Get the files for this gym.
     */
    public function files(): MorphToMany
    {
        return $this->morphToMany(File::class, 'fileable')->withPivot('flag');
    }
}
