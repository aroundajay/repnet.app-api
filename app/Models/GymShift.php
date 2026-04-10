<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GymShift extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'gym_id',
        'day_of_week',
        'start_time',
        'end_time',
        'day_pass_enabled',
    ];

    protected function casts(): array
    {
        return [
            'day_pass_enabled' => 'boolean',
        ];
    }

    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    public function gymShiftPlans(): HasMany
    {
        return $this->hasMany(GymShiftPlan::class);
    }
}
