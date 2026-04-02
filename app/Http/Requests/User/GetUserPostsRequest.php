<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Get User Posts Form Request
 *
 * Validates optional query parameters for fetching a user's public posts:
 * - per_page, cursor for cursor-based pagination.
 *
 * The userId comes from the route parameter — no body/query validation needed for it.
 * Used by GetUserPostsAction.
 *
 * Flow: GetUserPostsRequest → GetUserPostsAction → UserPostsService → UserPostsRepository
 */
class GetUserPostsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Any authenticated user may view another user's public posts.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Validation rules.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Items per page — defaults to 20 in the service layer
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],

            // Opaque cursor string returned by the previous page
            'cursor'   => ['nullable', 'string'],
        ];
    }
}
