<?php

namespace App\Services;

use App\Models\Otp;
use App\Repositories\OtpRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Arr;
/**
 * OTP Service
 * 
 * Handles OTP business logic including generation, validation,
 * data formatting, and coordination with repository.
 */
class OtpService
{
    /**
     * Create a new OTP service instance.
     */
    public function __construct(
        protected OtpRepository $repository,
        protected UserRepository $userRepository
    ) {}

    /**
     * Get the OTP repository instance.
     *
     * @return OtpRepository
     */
    public function getRepository(): OtpRepository
    {
        return $this->repository;
    }

    /*
    |--------------------------------------------------------------------------
    | OTP Generation
    |--------------------------------------------------------------------------
    */

    /**
     * Generate and store a new OTP.
     *
     * @param string $type OTP type (login, update_password, etc.)
     * @param string $identifier Email or mobile number
     * @param array $userProvidedRequestData Additional data to store
     * @param string|null $userId Optional user ID
     * @return Otp The created OTP record
     */
    public function generate(
        string $type,
        string $identifier,
        array $userProvidedRequestData = [],
        ?string $userId = null
    ): Otp {
        // Invalidate any previous active OTPs for this identifier/type
        $this->repository->invalidatePreviousOtps($identifier, $type);

        // Generate a new OTP code
        $code = $this->generateCode();

        // Get type configuration
        $typeConfig = $this->getTypeConfig($type);

        // Build the standardized data payload
        $data = $this->buildDataPayload($type, $identifier, $typeConfig, $userProvidedRequestData);

        // Calculate expiration time
        $expiryMinutes = $typeConfig['expiry_minutes'] ?? config('otp.code.expiry_minutes', 10);
        $expiredAt = now()->addMinutes($expiryMinutes);

        // Create the OTP record
        return $this->repository->create([
            'user_id' => $userId,
            'type' => $type,
            'otp' => $code, // Will be encrypted by repository
            'identifier' => $identifier,
            'data' => $data,
            'sent_at' => now(),
            'expired_at' => $expiredAt,
        ]);
    }

    /**
     * Generate a random numeric OTP code.
     *
     * @return string
     */
    protected function generateCode(): string
    {
        $length = config('otp.code.length', 6);
        
        // Generate cryptographically secure random digits
        $min = pow(10, $length - 1);
        $max = pow(10, $length) - 1;

        return (string) random_int($min, $max);
    }

    /**
     * Get the plain text OTP code for sending.
     * This decrypts the stored OTP.
     *
     * @param Otp $otp The OTP model
     * @return string|null
     */
    public function getPlainCode(Otp $otp): ?string
    {
        try {
            return \Illuminate\Support\Facades\Crypt::decryptString($otp->otp);
        } catch (\Exception $e) {
            return null;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Data Payload Building
    |--------------------------------------------------------------------------
    */

    /**
     * Build standardized data payload for OTP.
     * Format: {callback: 'url', value: {...data}}
     *
     * @param string $type OTP type
     * @param string $identifier Email or mobile number
     * @param array $typeConfig Type configuration
     * @param array $userProvidedRequestData Additional data from request
     * @return array
     */
    protected function buildDataPayload(string $type, string $identifier, array $typeConfig, array $userProvidedRequestData): array
    {
        // Get the callback URL for this type
        $callback = $typeConfig['callback'] ?? false;

        // Build the value based on type
        $value = $this->buildValueForType($type, $identifier, $userProvidedRequestData);

        return [
            'callback' => $callback,
            'value' => $value,
        ];
    }

    /**
     * Build the value portion of data based on OTP type.
     *
     * @param string $type OTP type
     * @param array $userProvidedRequestData Additional data from request
     * @return array
     */
    protected function buildValueForType(string $type, string $identifier, array $userProvidedRequestData): array
    {
        // Type-specific value building
        switch ($type) {
            case 'signup':
                $data = Arr::only($userProvidedRequestData, ['name', 'password']);

                $isEmail = $this->isEmail($identifier);
                $data[$isEmail ? 'email' : 'mobile'] = $identifier;
                $data[$isEmail ? 'email_verified_at' : 'mobile_verified_at'] = now();

                return $data;

                case 'invite_to_gym':
                    $data = Arr::only($userProvidedRequestData, ['name']);
    
                    $isEmail = $this->isEmail($identifier);
                    $data[$isEmail ? 'email' : 'mobile'] = $identifier;
                    $data[$isEmail ? 'email_verified_at' : 'mobile_verified_at'] = now();

                    $user = $this->userRepository->create($data);

                    return array_merge([
                        'user_id' => $user->id,
                    ], Arr::only($userProvidedRequestData, ['gym_id', 'role', 'status']));
    
            case 'login':
                return [
                    'identifier' => $identifier,
                ];

            case 'update_password':
                $user = $this->userRepository->findByIdentifier($identifier);

                if (!$user) {
                    throw new \Exception('User not found');
                }

                return array_merge(
                    $userProvidedRequestData,
                    [
                        'id' => $user->id,
                    ]
                );

            case 'update_email':
                if (!auth()->user()) {
                    throw new \Exception('User not authenticated');
                }
                return [
                    'id' => auth()->user()->id,
                    'email' => $identifier,
                    'email_verified_at' => now(),
                ];
            case 'update_mobile':
                if (!auth()->user()) {
                    throw new \Exception('User not authenticated');
                }
                return [
                    'id' => auth()->user()->id,
                    'mobile' => $identifier,
                    'mobile_verified_at' => now(),
                ];
            default:
                // For unknown types, include all additional data
                return $userProvidedRequestData;
        }
    }

    /**
     * Generate a unique reset token.
     *
     * @return string
     */
    protected function generateResetToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /*
    |--------------------------------------------------------------------------
    | Configuration Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Get configuration for a specific OTP type.
     *
     * @param string $type OTP type
     * @return array
     */
    public function getTypeConfig(string $type): array
    {
        return config("otp.types.{$type}", [
            'expiry_minutes' => config('otp.code.expiry_minutes', 10),
            'callback' => '',
            'description' => $type,
        ]);
    }

    /**
     * Get the expiry time for an OTP.
     *
     * @param Otp $otp The OTP model
     * @return int Minutes until expiry
     */
    public function getExpiryMinutes(Otp $otp): int
    {
        if ($otp->expired_at->isPast()) {
            return 0;
        }

        return now()->diffInMinutes($otp->expired_at);
    }

    /*
    |--------------------------------------------------------------------------
    | Validation & Verification
    |--------------------------------------------------------------------------
    */

    /**
     * Check if a new OTP can be requested (cooldown check).
     *
     * @param string $identifier Email or mobile
     * @param string $type OTP type
     * @return bool
     */
    public function canRequestOtp(string $identifier, string $type): bool
    {
        $cooldown = config('otp.security.resend_cooldown_seconds', 60);
        return $this->repository->canRequestNewOtp($identifier, $type, $cooldown);
    }

    /**
     * Get remaining cooldown time in seconds.
     *
     * @param string $identifier Email or mobile
     * @param string $type OTP type
     * @return int
     */
    public function getRemainingCooldown(string $identifier, string $type): int
    {
        $cooldown = config('otp.security.resend_cooldown_seconds', 60);
        return $this->repository->getRemainingCooldown($identifier, $type, $cooldown);
    }

    /**
     * Verify an OTP code.
     *
     * @param string $identifier Email or mobile
     * @param string $type OTP type
     * @param string $code The code to verify
     * @return array{success: bool, otp: ?Otp, message: string}
     */
    public function verify(string $identifier, string $type, string $code): array
    {
        // Find the latest active OTP
        $otp = $this->repository->findLatestActive($identifier, $type);

        if (!$otp) {
            return [
                'success' => false,
                'otp' => null,
                'message' => 'No active OTP found. Please request a new one.',
            ];
        }

        // Check if max attempts exceeded
        $maxAttempts = config('otp.security.max_attempts', 5);
        if ($otp->failed_attempts >= $maxAttempts) {
            return [
                'success' => false,
                'otp' => $otp,
                'message' => 'Maximum verification attempts exceeded. Please request a new OTP.',
            ];
        }

        // Verify the code
        $plainCode = $this->getPlainCode($otp);
        if ($plainCode !== $code) {
            // Increment failed attempts
            $this->repository->incrementFailedAttempts($otp);

            $remainingAttempts = $maxAttempts - ($otp->failed_attempts + 1);
            return [
                'success' => false,
                'otp' => $otp,
                'message' => "Invalid OTP code. {$remainingAttempts} attempts remaining.",
            ];
        }

        // Mark as succeeded
        $this->repository->markAsSucceeded($otp);

        return [
            'success' => true,
            'otp' => $otp->fresh(),
            'message' => 'OTP verified successfully.',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Identifier Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Determine if identifier is an email.
     *
     * @param string $identifier The identifier to check
     * @return bool
     */
    public function isEmail(string $identifier): bool
    {
        return filter_var($identifier, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Determine if identifier is a mobile number.
     *
     * @param string $identifier The identifier to check
     * @return bool
     */
    public function isMobile(string $identifier): bool
    {
        // Basic mobile number validation
        // Adjust the regex pattern based on your requirements
        return preg_match('/^\+?[1-9]\d{6,14}$/', $identifier) === 1;
    }

    /**
     * Get the channel type for sending (email or sms).
     *
     * @param string $identifier The identifier
     * @return string 'email' or 'sms'
     */
    public function getChannelType(string $identifier): string
    {
        return $this->isEmail($identifier) ? 'email' : 'sms';
    }

    /*
    |--------------------------------------------------------------------------
    | Message Building
    |--------------------------------------------------------------------------
    */

    /**
     * Build SMS message for OTP.
     *
     * @param string $code The OTP code
     * @param string $type The OTP type
     * @return string
     */
    public function buildSmsMessage(string $code, string $type): string
    {
        $template = config('otp.messages.sms');
        $typeConfig = $this->getTypeConfig($type);
        $expiryMinutes = $typeConfig['expiry_minutes'] ?? config('otp.code.expiry_minutes', 10);

        return str_replace(
            [':app_name', ':code', ':expiry'],
            [config('app.name'), $code, $expiryMinutes],
            $template
        );
    }
}
