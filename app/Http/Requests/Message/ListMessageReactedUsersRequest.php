<?php

namespace App\Http\Requests\Message;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListMessageReactedUsersRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return request()->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // To get reaction types without instantiation if possible, or we can hardcode.
        // Actually, HasReactions::getReactionTypes() can be used since it's a static method.
        // Wait, HasReactions is a trait. We can't call HasReactions::getReactionTypes(), we need a model that uses it.
        // App\Models\Message uses it.
        return [
            'reaction' => [
                'sometimes',
                'nullable',
                'string',
                Rule::in(\App\Models\Message::getReactionTypes()),
            ],
            'per_page' => 'sometimes|integer|min:1|max:100',
            'cursor' => 'sometimes|nullable|string',
        ];
    }
}
