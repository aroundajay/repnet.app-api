<?php

namespace App\Http\Requests\Gym;

use Illuminate\Foundation\Http\FormRequest;

/**
 * List Gym Form Request
 *
 * Validates optional query parameters for listing gyms:
 * - latitude/longitude (both together or not at all)
 * - q: search gym names (partial match)
 * - per_page, cursor for pagination.
 */
class ListGymRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Any authenticated user can list gyms.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Both latitude and longitude must be provided together or not at all.
     * Uses required_with to enforce this pairing.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Latitude: -90 to 90, required if longitude is provided
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],

            // Longitude: -180 to 180, required if latitude is provided
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],

            // Items per page: optional, defaults to 15 in the service layer
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],

            // Cursor: opaque string for cursor-based pagination (auto-read by Laravel)
            'cursor' => ['nullable', 'string'],

            // Search: filter gyms by name (partial match, case-insensitive when DB supports it)
            'q' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Prepare the data for validation.
     * Trim whitespace from search term so empty-looking strings are not sent to the backend.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('q') && is_string($this->q)) {
            $this->merge(['q' => trim($this->q)]);
        }
    }

    /**
     * Get custom attribute names for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'latitude' => 'latitude',
            'longitude' => 'longitude',
            'q' => 'search query',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'latitude.required_with' => 'Latitude is required when longitude is provided.',
            'longitude.required_with' => 'Longitude is required when latitude is provided.',
        ];
    }
}
