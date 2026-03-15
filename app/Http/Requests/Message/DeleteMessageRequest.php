<?php

namespace App\Http\Requests\Message;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Delete Message Form Request
 *
 * Validates incoming requests to delete a message.
 * No body params are needed — the message ID comes from the route.
 * Ownership (sender check) is enforced in DeleteMessageAction::authorize().
 *
 * Used by DeleteMessageAction.
 */
class DeleteMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Ownership is checked in the action; only require authentication here.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     * No body or query params — the message ID comes from the route parameter.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }
}
