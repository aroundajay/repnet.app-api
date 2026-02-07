<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Challenge model for gym competitions.
 * 
 * Supports different metric types: reps, time, weight.
 * Has scheduled start/end dates and optional rewards.
 */
class Challenge extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * Metric type constants.
     */
    public const METRIC_REPS = 'reps';
    public const METRIC_TIME = 'time';
    public const METRIC_WEIGHT = 'weight';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'gym_id',
        'title',
        'description',
        'workout_type_id',
        'metric_type',
        'start_date',
        'end_date',
        'reward',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the gym that hosts this challenge.
     */
    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    /**
     * Get the workout type for this challenge.
     */
    public function workoutType(): BelongsTo
    {
        return $this->belongsTo(WorkoutType::class);
    }

    /**
     * Get all submissions for this challenge.
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(ChallengeSubmission::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if the challenge is currently active.
     */
    public function isActive(): bool
    {
        $today = now()->toDateString();
        return $this->start_date <= $today && $this->end_date >= $today;
    }

    /**
     * Check if the challenge has ended.
     */
    public function hasEnded(): bool
    {
        return $this->end_date < now()->toDateString();
    }

    /**
     * Check if the challenge hasn't started yet.
     */
    public function isUpcoming(): bool
    {
        return $this->start_date > now()->toDateString();
    }
}
