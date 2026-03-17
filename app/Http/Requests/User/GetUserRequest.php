<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Get User Form Request
 *
 * Validates incoming requests to fetch a user by ID.
 * No body params needed — the user ID comes from the route parameter.
 * Used by GetUserAction.
 */
class GetUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Only authenticated users can look up other users.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     * No body or query params — the user ID comes from the route parameter.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }
}
