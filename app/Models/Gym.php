<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Gym model representing fitness facilities.
 * 
 * Supports location-based discovery and public/private visibility.
 * Has role-based membership system (OWNER, TRAINER, MEMBER).
 */
class Gym extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'description',
        'location_lat',
        'location_lng',
        'is_public',
        'user_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'location_lat' => 'decimal:8',
            'location_lng' => 'decimal:8',
            'is_public' => 'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the owner/creator of this gym.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get all gym memberships.
     */
    public function gymUsers(): HasMany
    {
        return $this->hasMany(GymUser::class);
    }

    /**
     * Get all workout videos for this gym.
     */
    public function workoutVideos(): HasMany
    {
        return $this->hasMany(WorkoutVideo::class);
    }

    /**
     * Get all notice posts for this gym.
     */
    public function noticePosts(): HasMany
    {
        return $this->hasMany(NoticePost::class);
    }

    /**
     * Get all challenges for this gym.
     */
    public function challenges(): HasMany
    {
        return $this->hasMany(Challenge::class);
    }

    /**
     * Get all partner requests for this gym.
     */
    public function partnerRequests(): HasMany
    {
        return $this->hasMany(PartnerRequest::class);
    }

    /**
     * Get the message thread for this gym (group chat).
     */
    public function messageThread(): MorphOne
    {
        return $this->morphOne(MessageThread::class, 'messageable');
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if the gym is public.
     */
    public function isPublic(): bool
    {
        return $this->is_public;
    }

    /**
     * Get the location as an array.
     */
    public function getLocationAttribute(): array
    {
        return [
            'lat' => $this->location_lat,
            'lng' => $this->location_lng,
        ];
    }
}
