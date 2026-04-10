<?php

namespace App\Http\Requests\GymShiftPlan;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create Gym Shift Plan Form Request
 *
 * Validates incoming requests to create a new pricing plan for a gym shift.
 * Flow: CreateGymShiftPlanRequest → CreateGymShiftPlanAction → GymShiftPlanService → GymShiftPlanRepository.
 */
class CreateGymShiftPlanRequest extends FormRequest
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
            // Price must be non-negative with at most 2 decimal places
            'price' => ['required', 'numeric', 'min:0', 'decimal:0,2'],

            // Duration in minutes, must be a positive integer
            'duration_minutes' => ['required', 'integer', 'min:30'],

            // Personal training enabled or not
            'personal_training_enabled' => ['required', 'boolean'],
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
            'price'            => 'price',
            'duration_minutes' => 'duration in minutes',
            'personal_training_enabled' => 'personal training enabled',
        ];
    }
}
