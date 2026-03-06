<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Reaction extends Model
{
    use HasUuids;

    protected $fillable = [
        'reactable_id',
        'reactable_type',
        'user_id',
        'reaction',
    ];

    /**
     * Summary of reactable
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo<Model, Reaction>
     */
    public function reactable()
    {
        return $this->morphTo();
    }

    /**
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User, Reaction>
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
