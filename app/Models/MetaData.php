<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MetaData extends Model
{
    use HasUuids, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'metadata';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * Get old values.
     */
    protected function value(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                json_decode($value);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return json_decode($value);
                }

                return $value;
            },
            set: function ($value) {
                if (is_array($value)) {
                    return json_encode($value);
                }

                return $value;
            }
        );
    }

    /**
     * Belongs to model.
     */
    public function metadataable()
    {
        return $this->morphTo();
    }

    /**
     * Get all of the metadata.
     */
    public function metadata()
    {
        return $this->morphMany(MetaData::class, 'metadataable')->with(['metadata']);
    }
}
