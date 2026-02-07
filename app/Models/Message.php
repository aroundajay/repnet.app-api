<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Message model for individual chat messages.
 * 
 * Belongs to a MessageThread for conversation grouping.
 * Uses bloom filter compatible read_by field for efficient read tracking.
 */
class Message extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'thread_id',
        'user_id',
        'message',
        'read_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'read_by', // Bloom filter data not useful in API responses
    ];

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
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Bloom Filter Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Get the read_by bloom filter as raw bytes.
     */
    public function getReadByFilter(): ?string
    {
        return $this->read_by;
    }

    /**
     * Set the read_by bloom filter from raw bytes.
     */
    public function setReadByFilter(string $filter): void
    {
        $this->read_by = $filter;
    }

    /**
     * Initialize an empty bloom filter for read tracking.
     * Default size: 128 bytes (1024 bits) for ~100 users with 1% false positive rate.
     */
    public function initializeReadByFilter(int $sizeInBytes = 128): void
    {
        $this->read_by = str_repeat("\0", $sizeInBytes);
    }
}
