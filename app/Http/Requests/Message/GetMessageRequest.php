<?php

namespace App\Http\Requests\Message;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Get Message Form Request
 *
 * Validates the request for fetching a single message by ID.
 * Route params (threadId, messageId) are validated in the action layer.
 * Used by GetMessageAction.
 */
class GetMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Handled in GetMessageAction::authorize() with thread/gym membership checks.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     * No body/query params needed — IDs come from route parameters.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }
}
