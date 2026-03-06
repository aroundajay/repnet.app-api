<?php

namespace App\Http\Requests\Feed;

use Illuminate\Foundation\Http\FormRequest;

/**
 * User Feed Form Request
 *
 * Validates optional query parameters for the user feed:
 * - latitude/longitude (both together or not at all) for distance sorting
 * - per_page, cursor for cursor-based pagination.
 *
 * Any authenticated user may call this endpoint.
 */
class UserFeedRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Authorization is implicit – any authenticated user can see their feed.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules.
     *
     * Latitude and longitude must be supplied together or not at all.
     * When present they constrain distance-based ordering of the feed.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Latitude: -90 to 90, required if longitude is provided
            'latitude'  => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],

            // Longitude: -180 to 180, required if latitude is provided
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],

            // Search radius for public posts bounding box (km).
            // Only meaningful when latitude + longitude are supplied.
            // Capped at 500 km in the service layer regardless of this value.
            'radius_km' => ['nullable', 'required_with:latitude', 'numeric', 'min:1', 'max:500'],

            // Items per page — defaults to 20 in the service layer
            'per_page'  => ['nullable', 'integer', 'min:1', 'max:100'],

            // Opaque cursor string returned by the previous page
            'cursor'    => ['nullable', 'string'],
        ];
    }

    /**
     * Custom attribute labels used in validation error messages.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'latitude'  => 'latitude',
            'longitude' => 'longitude',
            'radius_km' => 'search radius',
        ];
    }

    /**
     * Custom error messages for coordinate pairing rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'latitude.required_with'  => 'Latitude is required when longitude is provided.',
            'longitude.required_with' => 'Longitude is required when latitude is provided.',
            'radius_km.required_with' => 'Search radius is required when coordinates are provided.',
            'radius_km.min'           => 'Search radius must be at least 1 km.',
            'radius_km.max'           => 'Search radius may not exceed 500 km.',
        ];
    }
}
