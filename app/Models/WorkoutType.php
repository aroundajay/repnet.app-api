<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * WorkoutType model for categorizing workouts.
 * 
 * Extensible table for different workout categories:
 * Weightlifting, Yoga, CrossFit, etc.
 */
class WorkoutType extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
    ];

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
}
