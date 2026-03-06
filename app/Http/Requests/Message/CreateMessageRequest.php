<?php

namespace App\Http\Requests\Message;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Create Message Form Request
 *
 * Validates incoming requests to send a new message within a thread.
 * Used by CreateMessageAction; ensures the message body is present
 * and any attached file IDs are valid UUIDs that exist in the files table.
 */
class CreateMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Only authenticated users can send messages.
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
            // Message body - required, non-empty string, max 10000 chars
            'message' => ['nullable', 'string', 'max:10000'],

            'is_public' => ['required', 'boolean'],

            // Optional file attachments - array of existing file UUIDs
            'files'   => ['nullable', 'array'],
            'files.*' => ['uuid', 'exists:files,id'],
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
            'files.*' => 'file',
        ];
    }
}
