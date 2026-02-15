<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Amenity extends Model
{
    use HasUuids;
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name'
    ];

    /**
     * Summary of gyms
     * @return BelongsToMany<Gym, Amenity, \Illuminate\Database\Eloquent\Relations\Pivot>
     */
    public function gyms(): BelongsToMany
    {
        return $this->belongsToMany(Gym::class, 'gym_amenities');
    }
}
