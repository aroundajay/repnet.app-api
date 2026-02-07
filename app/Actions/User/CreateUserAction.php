<?php

namespace App\Actions\User;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Create User Action
 * 
 * Handles user creation with validation.
 * This action is designed to be called programmatically (no HTTP interface).
 * 
 * Responsibilities:
 * - Validate input data
 * - Delegate user creation to UserService
 * - Return created user or throw validation exception
 * 
 * Uses Action <-> Service <-> Repository pattern.
 */
class CreateUserAction
{
    /**
     * Create a new action instance.
     */
    public function __construct(
        protected UserService $userService
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Main Action Logic
    |--------------------------------------------------------------------------
    */

    /**
     * Handle the user creation action.
     *
     * @param array $data User data containing name, email/mobile, and optional password
     * @return array{success: bool, message: string, status_code: int, data: User}
     * @throws ValidationException If validation fails
     */
    public function handle(array $data): array
    {
        // Step 1: Validate the input data
        $validated = $this->validate($data);

        // Step 2: Create user via service
        $user = $this->userService->create($validated);

        // Step 3: Log the creation
        $this->logUserCreation($user);

        return [
            'success' => true,
            'message' => 'User created successfully',
            'status_code' => 200,
            'data' => $user,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    /**
     * Validate the input data.
     *
     * @param array $data Raw input data
     * @return array Validated data
     * @throws ValidationException If validation fails
     */
    protected function validate(array $data): array
    {
        $validator = Validator::make($data, $this->rules(), $this->messages());

        // Add custom validation: either email or mobile must be provided
        $validator->after(function ($validator) use ($data) {
            $hasEmail = !empty($data['email']);
            $hasMobile = !empty($data['mobile']);

            if (!$hasEmail && !$hasMobile) {
                $validator->errors()->add(
                    'identifier',
                    'Either email or mobile number is required.'
                );
            }
        });

        // Run validation
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Get the validation rules.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            // Name is required
            'name' => [
                'required',
                'string',
                'min:2',
                'max:255',
            ],

            // Password is optional, but if provided must meet requirements
            'password' => [
                'sometimes',
                'nullable',
                'string',
                'min:6',
            ],

            // Email is optional (but either email or mobile required)
            'email' => [
                'sometimes',
                'nullable',
                'string',
                'email',
                'max:255',
                'unique:users,email',
            ],

            // Mobile is optional (but either email or mobile required)
            'mobile' => [
                'sometimes',
                'nullable',
                'string',
                'regex:/^\+?[1-9]\d{6,14}$/',
                'unique:users,mobile',
            ],

            // Avatar is optional
            'avatar' => [
                'sometimes',
                'nullable',
                'string',
                'max:500',
            ],

            'email_verified_at' => [
                'sometimes',
                'nullable'
            ],

            'mobile_verified_at' => [
                'sometimes',
                'nullable'
            ],
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array
     */
    protected function messages(): array
    {
        return [
            'name.required' => 'The name is required.',
            'name.min' => 'The name must be at least 2 characters.',
            'name.max' => 'The name must not exceed 255 characters.',
            'password.min' => 'The password must be at least 6 characters.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email is already registered.',
            'mobile.regex' => 'Please provide a valid mobile number.',
            'mobile.unique' => 'This mobile number is already registered.',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Log the user creation event.
     *
     * @param User $user The created user
     * @return void
     */
    protected function logUserCreation(User $user): void
    {
        Log::info('User created successfully', [
            'user_id' => $user->id,
            'name' => $user->name,
            'has_email' => !empty($user->email),
            'has_mobile' => !empty($user->mobile),
        ]);
    }
}
