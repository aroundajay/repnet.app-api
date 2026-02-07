<?php

namespace App\Http\Requests\Gym;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Create Gym Form Request
 *
 * Validates incoming requests to create a new gym.
 * Used by CreateGymAction; ensures name, location, and optional fields are valid.
 */
class CreateGymRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Only authenticated users can create a gym (owner).
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Gym name - required, max 255 to match typical string column
            'name' => ['required', 'string', 'max:255'],

            // Optional description
            'description' => ['nullable', 'string', 'max:5000'],

            // Latitude: -90 to 90 (decimal 10,8 in migration)
            'location_lat' => ['required', 'numeric', 'between:-90,90'],

            // Longitude: -180 to 180 (decimal 11,8 in migration)
            'location_lng' => ['required', 'numeric', 'between:-180,180'],

            // Public visibility - optional, defaults to false in model
            'is_public' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'location_lat' => 'latitude',
            'location_lng' => 'longitude',
            'is_public' => 'public visibility',
        ];
    }
}
