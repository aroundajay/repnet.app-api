<?php

namespace App\Http\Requests\GymShift;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Gym Shift Form Request
 *
 * Validates incoming PATCH requests to update an existing gym shift.
 * All fields are optional (sometimes) – only provided fields are updated.
 * Flow: UpdateGymShiftRequest → UpdateGymShiftAction → GymShiftService → GymShiftRepository.
 */
class UpdateGymShiftRequest extends FormRequest
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
            'day_of_week'      => ['sometimes', 'string', 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'],
            'start_time'       => ['sometimes', 'date_format:H:i,H:i:s'],
            'end_time'         => ['sometimes', 'date_format:H:i,H:i:s', 'after:start_time'],
            'day_pass_enabled' => ['sometimes', 'boolean'],
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
            'day_of_week'      => 'day of week',
            'start_time'       => 'start time',
            'end_time'         => 'end time',
            'day_pass_enabled' => 'day pass enabled',
        ];
    }
}
