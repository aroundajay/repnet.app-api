<?php

namespace App\Http\Requests\Notification;

use Illuminate\Foundation\Http\FormRequest;

class ListNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page'    => ['nullable', 'integer', 'min:1', 'max:100'],
            'cursor'      => ['nullable', 'string'],
            'unread_only' => ['nullable', 'boolean'],
        ];
    }
}
