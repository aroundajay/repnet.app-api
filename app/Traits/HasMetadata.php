<?php

namespace App\Traits;

use App\Models\MetaData;
use Illuminate\Support\Arr;

trait HasMetadata
{
    /**
     * Get all of the metadata for the model.
     */
    public function metadata()
    {
        return $this->morphMany(MetaData::class, 'metadataable');
    }

    /**
     * Update metadata.
     */
    public function updateMetadata($metadata = [])
    {
        if (! isset($this->updateable_metadata) || ! isset($this->multiple_metadata)) {
            return;
        }

        $updateable_metadata = $this->updateable_metadata;
        $multiple_metadata = $this->multiple_metadata;


        $single_instance_metadata = array_filter($metadata, function ($metadata) use ($updateable_metadata, $multiple_metadata) {
            return in_array($metadata['key'], $updateable_metadata) && ! in_array($metadata['key'], $multiple_metadata);
        });


        foreach ($single_instance_metadata as $single_metadata) {
            $this->metadata()->updateOrCreate(
                Arr::only($single_metadata, ['key']),
                Arr::only($single_metadata, ['value'])
            );
        }

        $multiple_instance_metadata = array_filter($metadata, function ($metadata) use ($updateable_metadata, $multiple_metadata) {
            return in_array($metadata['key'], $updateable_metadata) && in_array($metadata['key'], $multiple_metadata);
        });

        // delete existing metadata
        $keys = collect($multiple_instance_metadata)->pluck('key');
        $uniqueKeys = $keys->unique()->values()->all();
        $this->metadata()->whereIn('key', $uniqueKeys)->delete();

        $this->metadata()->createMany(
            $multiple_instance_metadata
        );
    }

    /**
     * Get the readable status.
     */
    protected function getMetadataKeyValueAttribute()
    {
        $multiple_metadata = $this->multiple_metadata;

        return $this->metadata()->whereNotIn('key', $this->hide_metadata ?? [])->get()->groupBy('key')->map(function ($items, $key) use ($multiple_metadata) {
            if (in_array($key, $multiple_metadata)) {
                return $items->pluck('value')->all();
            }

            return $items->pluck('value')->first();
        });
    }
}
