<?php

namespace App\Http\Requests\GymShift;

use Illuminate\Foundation\Http\FormRequest;

/**
 * List Gym Shifts Form Request
 *
 * Validates optional query parameters for listing shifts of a specific gym.
 *   - per_page: items per page (cursor pagination)
 *   - cursor: opaque cursor string for next/previous page
 *
 * Flow: ListGymShiftRequest → ListGymShiftAction → GymShiftService → GymShiftRepository.
 */
class ListGymShiftRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Role-based authorization is handled inside the action's authorize() method.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'cursor'   => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom human-readable attribute names for validator error messages.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'per_page' => 'items per page',
            'cursor'   => 'pagination cursor',
        ];
    }
}
