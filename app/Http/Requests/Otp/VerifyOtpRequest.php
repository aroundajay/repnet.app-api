<?php

namespace App\Http\Requests\Otp;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Verify OTP Form Request
 * 
 * Validates incoming OTP verification requests.
 * Ensures identifier, type, and code are properly formatted.
 */
class VerifyOtpRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // OTP verification requests are public (for login, registration, etc.)
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
            // OTP type - must be a valid configured type
            'type' => [
                'required',
                'string',
                Rule::in($this->getValidTypes()),
            ],

            // Identifier - can be email or mobile number
            'identifier' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    if (!$this->isValidIdentifier($value)) {
                        $fail('The identifier must be a valid email address or mobile number.');
                    }
                },
            ],

            // OTP code - required numeric string
            'code' => [
                'required',
                'string',
                'size:' . config('otp.code.length', 6),
                'regex:/^\d+$/', // Must be digits only
            ],
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $codeLength = config('otp.code.length', 6);

        return [
            'type.required' => 'The OTP type is required.',
            'type.in' => 'The selected OTP type is invalid.',
            'identifier.required' => 'The identifier (email or mobile) is required.',
            'identifier.max' => 'The identifier must not exceed 255 characters.',
            'code.required' => 'The verification code is required.',
            'code.size' => "The verification code must be exactly {$codeLength} digits.",
            'code.regex' => 'The verification code must contain only digits.',
        ];
    }

    /**
     * Get custom attribute names.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'type' => 'OTP type',
            'identifier' => 'email/mobile',
            'code' => 'verification code',
        ];
    }

    /**
     * Prepare the data for validation.
     * Normalize and sanitize input before validation.
     */
    protected function prepareForValidation(): void
    {
        // Normalize identifier (trim whitespace, lowercase email)
        if ($this->has('identifier')) {
            $identifier = trim($this->identifier);
            
            // If it looks like an email, lowercase it
            if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
                $identifier = strtolower($identifier);
            }
            
            $this->merge([
                'identifier' => $identifier,
            ]);
        }

        // Normalize type to lowercase
        if ($this->has('type')) {
            $this->merge([
                'type' => strtolower(trim($this->type)),
            ]);
        }

        // Trim whitespace from code
        if ($this->has('code')) {
            $this->merge([
                'code' => trim($this->code),
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Get valid OTP types from configuration.
     *
     * @return array
     */
    protected function getValidTypes(): array
    {
        return array_keys(config('otp.types', []));
    }

    /**
     * Validate if identifier is a valid email or mobile number.
     *
     * @param string $value The identifier to validate
     * @return bool
     */
    protected function isValidIdentifier(string $value): bool
    {
        return $this->isValidEmail($value) || $this->isValidMobile($value);
    }

    /**
     * Check if value is a valid email address.
     *
     * @param string $value The value to check
     * @return bool
     */
    protected function isValidEmail(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Check if value is a valid mobile number.
     * Supports international format with optional + prefix.
     *
     * @param string $value The value to check
     * @return bool
     */
    protected function isValidMobile(string $value): bool
    {
        // E.164 format: +[country code][number], 7-15 digits total
        return preg_match('/^\+?[1-9]\d{6,14}$/', $value) === 1;
    }
}
