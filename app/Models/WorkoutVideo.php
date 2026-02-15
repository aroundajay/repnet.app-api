<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * WorkoutVideo model for gym workout content.
 * 
 * Stores workout videos with metadata for categorization.
 * Supports comments/discussion through message threads.
 */
class WorkoutVideo extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'gym_id',
        'file_id',
        'title',
        'description',
        'workout_type_id',
        'muscle_group',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the gym that owns this video.
     */
    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    /**
     * Get the file record for this video.
     */
    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }

    /**
     * Get the workout type for this video.
     */
    public function workoutType(): BelongsTo
    {
        return $this->belongsTo(WorkoutType::class);
    }

    /**
     * Get the message thread for comments on this video.
     */
    public function messageThread(): MorphOne
    {
        return $this->morphOne(MessageThread::class, 'messageable');
    }

    /**
     * Get the files for this gym.
     */
    public function files(): MorphToMany
    {
        return $this->morphToMany(File::class, 'fileable')->withPivot('flag');
    }
}
