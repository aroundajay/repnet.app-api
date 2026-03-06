<?php

namespace App\Http\Requests\Message;

use Illuminate\Foundation\Http\FormRequest;

/**
 * List Messages Form Request
 *
 * Validates query parameters for paginating messages within a thread.
 * Used by ListMessageAction.
 */
class ListMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Handled in ListMessageAction::authorize() with thread/gym membership checks.
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
            // Number of messages per page — defaults to 20 in the service layer
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],

            // Opaque cursor string returned by the previous page
            'cursor'   => ['nullable', 'string'],
        ];
    }
}
