<?php

namespace App\Models;

use App\Traits\HasMetadata;
use App\Traits\HasMessageThread;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Gym model representing fitness facilities.
 *
 * Supports location-based discovery and public/private visibility.
 * Has role-based membership system (OWNER, TRAINER, MEMBER).
 */
class Gym extends Model
{
    use HasFactory, HasUuids, SoftDeletes, HasMetadata, HasMessageThread;

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

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['metadata_key_value'];

    /**
     * Array of keys that are updateable of metadata
     */
    protected $updateable_metadata = [
        'address',
        'offers_personal_training',
    ];

    /**
     * Array of keys that are multiple updateable of metadata
     */
    protected $multiple_metadata = [];

    /**
     * Array of keys that are hide in metadata
     */
    protected $hide_metadata = [];

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
     * Get the files for this gym.
     */
    public function files(): MorphToMany
    {
        return $this->morphToMany(File::class, 'fileable')->withPivot('flag');
    }

    /**
     * Summary of amenities
     * @return BelongsToMany<Amenity, Gym, \Illuminate\Database\Eloquent\Relations\Pivot>
     */
    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class, 'gym_amenities');
    }

    /**
     * Summary of workoutTypes
     * @return BelongsToMany<Gym, WorkoutType, \Illuminate\Database\Eloquent\Relations\Pivot>
     */
    public function workoutTypes(): BelongsToMany
    {
        return $this->belongsToMany(WorkoutType::class, 'gym_workout_types');
    }

    /**
     * Get all gym shifts for this gym.
     */
    public function gymShifts(): HasMany
    {
        return $this->hasMany(GymShift::class);
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
     * Get the logo path
     */
    public function getLogoAttribute(): string
    {
        $logo = $this->files()->where('flag', 'logo')->first();
        return $logo ? $logo->path : 'assets/icons/default-gym-logo.png';
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
