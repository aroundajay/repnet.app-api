<?php

namespace App\Http\Requests\GymShift;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create Gym Shift Form Request
 *
 * Validates incoming requests to create a new gym shift.
 * Flow: CreateGymShiftRequest → CreateGymShiftAction → GymShiftService → GymShiftRepository.
 */
class CreateGymShiftRequest extends FormRequest
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
            // Day of week: enum values
            'day_of_week' => ['required', 'string', 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'],

            // Start time in HH:MM or HH:MM:SS format
            'start_time' => ['required', 'date_format:H:i,H:i:s'],

            // End time must be after start_time
            'end_time' => ['required', 'date_format:H:i,H:i:s', 'after:start_time'],

            // Whether day passes are enabled for this shift
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
