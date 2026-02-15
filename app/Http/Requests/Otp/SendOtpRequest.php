<?php

namespace App\Http\Requests\Otp;

use App\Repositories\UserRepository;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Send OTP Form Request
 * 
 * Validates incoming OTP send requests.
 * Ensures type, identifier, and data are properly formatted.
 */
class SendOtpRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // OTP requests are generally public (for login, registration, etc.)
        // Add specific authorization logic if needed
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge(
            $this->baseRules(),
            $this->typeSpecificRules()
        );
    }

    /**
     * Get base validation rules that apply to all OTP types.
     *
     * @return array
     */
    protected function baseRules(): array
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

            // Additional data - required for some types, optional for others
            'data' => $this->getDataFieldRules(),

            // Device info - optional metadata
            'data.device_info' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    /**
     * Get validation rules specific to the OTP type.
     * Each type can have its own required fields in the data object.
     *
     * @return array
     */
    protected function typeSpecificRules(): array
    {
        $type = strtolower($this->input('type', ''));

        return match ($type) {
            'signup' => $this->signupRules(),
            'update_password' => $this->updatePasswordRules(),
            default => [],
        };
    }

    /**
     * Get validation rules for signup type.
     * Requires name field in data.
     * Also validates that identifier (email/mobile) doesn't already exist in users table.
     *
     * @return array
     */
    protected function signupRules(): array
    {
        return [
            'data.name' => [
                'required',
                'string',
                'min:2',
                'max:255',
            ],
            'data.password' => [
                'required',
                'string',
                'min:6',
            ],
            // Ensure identifier doesn't already exist in users table
            'identifier' => [
                function ($attribute, $value, $fail) {
                    if ($this->identifierExistsInUsers($value)) {
                        $fail($this->getIdentifierExistsMessage());
                    }
                },
            ],
        ];
    }

    protected function updatePasswordRules(): array
    {
        return [
            'data.password' => [
                'required',
                'string',
                'min:6',
            ],
        ];
    }

    /**
     * Check if the identifier already exists in the users table.
     * Checks both email and mobile columns.
     * Uses UserRepository to encapsulate data access.
     *
     * @param string $value The identifier to check
     * @return bool True if identifier exists in users table
     */
    protected function identifierExistsInUsers(string $value): bool
    {
        // Resolve repository from container
        $userRepository = app(UserRepository::class);

        return $userRepository->existsByIdentifier($value);
    }

    /**
     * Get appropriate error message based on identifier type.
     *
     * @return string
     */
    protected function getIdentifierExistsMessage(): string
    {
        if ($this->isEmailIdentifier()) {
            return 'This email is already registered. Please login instead.';
        }

        return 'This mobile number is already registered. Please login instead.';
    }

    /**
     * Get rules for the data field based on type.
     * Some types require data to be present.
     *
     * @return array
     */
    protected function getDataFieldRules(): array
    {
        $type = strtolower($this->input('type', ''));

        // Types that require the data field
        $typesRequiringData = ['signup', 'update_password'];

        if (in_array($type, $typesRequiringData)) {
            return ['required', 'array'];
        }

        return ['nullable', 'array'];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return array_merge(
            $this->baseMessages(),
            $this->typeSpecificMessages()
        );
    }

    /**
     * Get base validation messages.
     *
     * @return array
     */
    protected function baseMessages(): array
    {
        return [
            'type.required' => 'The OTP type is required.',
            'type.in' => 'The selected OTP type is invalid. Valid types are: ' . implode(', ', $this->getValidTypes()),
            'identifier.required' => 'The identifier (email or mobile) is required.',
            'identifier.max' => 'The identifier must not exceed 255 characters.',
            'data.array' => 'The data field must be an object.',
            'data.required' => 'The data field is required for this OTP type.',
        ];
    }

    /**
     * Get type-specific validation messages.
     *
     * @return array
     */
    protected function typeSpecificMessages(): array
    {
        $type = strtolower($this->input('type', ''));

        return match ($type) {
            'signup' => [
                'data.name.required' => 'The name is required for signup.',
                'data.name.string' => 'The name must be a valid string.',
                'data.name.min' => 'The name must be at least 2 characters.',
                'data.name.max' => 'The name must not exceed 255 characters.',
                'identifier.exists' => 'This email or mobile number is already registered.',
            ],
            'update_password' => [
                'data.password.required' => 'The password is required for update password.',
                'data.password.string' => 'The password must be a valid string.',
                'data.password.min' => 'The password must be at least 6 characters.',
            ],
            default => [],
        };
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
            'data' => 'additional data',
            'data.name' => 'name',
            'data.device_info' => 'device information',
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
    }

    /**
     * Handle a passed validation attempt.
     * Add request metadata to the data array.
     */
    protected function passedValidation(): void
    {
        // Inject request metadata into data
        $data = $this->input('data', []);
        
        // Add IP address for security logging
        $data['ip_address'] = $this->ip();
        
        // Add user agent info
        $data['user_agent'] = $this->userAgent();

        $this->merge(['data' => $data]);
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
        // Also allow without + for flexibility
        return preg_match('/^\+?[1-9]\d{6,14}$/', $value) === 1;
    }

    /**
     * Get the identifier type (email or mobile).
     *
     * @return string
     */
    public function getIdentifierType(): string
    {
        $identifier = $this->input('identifier', '');
        
        if ($this->isValidEmail($identifier)) {
            return 'email';
        }
        
        return 'mobile';
    }

    /**
     * Check if the identifier is an email.
     *
     * @return bool
     */
    public function isEmailIdentifier(): bool
    {
        return $this->getIdentifierType() === 'email';
    }

    /**
     * Check if the identifier is a mobile number.
     *
     * @return bool
     */
    public function isMobileIdentifier(): bool
    {
        return $this->getIdentifierType() === 'mobile';
    }
}
