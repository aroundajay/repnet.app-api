<?php

namespace App\Models;

use App\Traits\HasMetadata;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * WorkoutType model for categorizing workouts.
 * 
 * Extensible table for different workout categories:
 * Weightlifting, Yoga, CrossFit, etc.
 */
class WorkoutType extends Model
{
    use HasFactory, HasUuids, SoftDeletes, HasMetadata;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
    ];

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
        'muscle_group',
    ];

    /**
     * Array of keys that are multiple updateable of metadata
     */
    protected $multiple_metadata = [
        'muscle_group',
    ];

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
     * Get all workout videos of this type.
     */
    public function workoutVideos(): HasMany
    {
        return $this->hasMany(WorkoutVideo::class);
    }

    /**
     * Get all challenges of this workout type.
     */
    public function challenges(): HasMany
    {
        return $this->hasMany(Challenge::class);
    }

    /**
     * Get all partner requests for this workout type.
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
     * Summary of gyms
     * @return BelongsToMany<Gym, WorkoutType, \Illuminate\Database\Eloquent\Relations\Pivot>
     */
    public function gyms(): BelongsToMany
    {
        return $this->belongsToMany(Gym::class, 'gym_workout_types');
    }
}
