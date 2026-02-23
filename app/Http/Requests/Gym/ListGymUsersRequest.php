<?php

namespace App\Http\Requests\Gym;

use App\Models\GymUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * List Gym Users Form Request
 *
 * Validates optional query parameters for listing users of a specific gym:
 * - q: search by user name or email (partial, case-insensitive match)
 * - status: filter by gym_users.status (pending|active|rejected)
 * - per_page, cursor: cursor-based pagination controls.
 *
 * Flow: ListGymUsersRequest (validation) -> ListGymUsersAction -> GymService -> GymUserRepository.
 */
class ListGymUsersRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Only authenticated users (handled by the auth:sanctum middleware on the route) can list gym users.
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
            // Search: partial match on user name OR email
            'q' => ['nullable', 'string', 'max:255'],

            // Status filter: must be one of the defined GymUser status constants
            'status' => [
                'nullable',
                'string',
                Rule::in([
                    GymUser::STATUS_PENDING,
                    GymUser::STATUS_ACTIVE,
                    GymUser::STATUS_REJECTED,
                ]),
            ],

            // Items per page: optional, capped at 100; defaults to 50 in the service layer
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],

            // Cursor: opaque string for cursor-based pagination (auto-read by Laravel)
            'cursor' => ['nullable', 'string'],
        ];
    }

    /**
     * Trim the search term before validation so blank strings are not forwarded.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('q') && is_string($this->q)) {
            $this->merge(['q' => trim($this->q)]);
        }
    }

    /**
     * Get custom human-readable attribute names for validator error messages.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'q'        => 'search query',
            'status'   => 'membership status',
            'per_page' => 'items per page',
            'cursor'   => 'pagination cursor',
        ];
    }

    /**
     * Get custom validation error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $validStatuses = implode(', ', [
            GymUser::STATUS_PENDING,
            GymUser::STATUS_ACTIVE,
            GymUser::STATUS_REJECTED,
        ]);

        return [
            'status.in' => "The membership status must be one of: {$validStatuses}.",
        ];
    }
}
