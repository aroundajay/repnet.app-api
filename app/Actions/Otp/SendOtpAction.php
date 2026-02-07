<?php

namespace App\Actions\Otp;

use App\Http\Requests\Otp\SendOtpRequest;
use App\Models\Otp;
use App\Models\User;
use App\Notifications\OtpNotification;
use App\Services\OtpService;
use App\Services\SmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Send OTP Action
 * 
 * Orchestrates the OTP sending process:
 * 1. Validates the request
 * 2. Checks rate limiting (cooldown)
 * 3. Generates and stores OTP via OtpService
 * 4. Sends OTP via appropriate channel (email or SMS)
 * 
 * Uses Laravel Actions pattern for clean, single-purpose code.
 */
class SendOtpAction
{
    use AsAction;

    /**
     * Create a new action instance.
     */
    public function __construct(
        protected OtpService $otpService,
        protected SmsService $smsService
    ) {}

    /*
    |--------------------------------------------------------------------------
    | HTTP Controller Interface
    |--------------------------------------------------------------------------
    */

    /**
     * Handle the action as an HTTP controller.
     * This method is called when the action is used as a route.
     *
     * @param SendOtpRequest $request The validated request
     * @return JsonResponse
     */
    public function asController(SendOtpRequest $request): array
    {
        // make sure to get the validated data
        $type = $request->validated('type');
        $identifier = $request->validated('identifier');
        $data = $request->validated('data', []);

        // Execute the action
        return $this->handle($type, $identifier, $data);
    }

    public function jsonResponse(array $data): JsonResponse
    {
        return response()->json($data, $data['status_code'] ?? 200);
    }

    /*
    |--------------------------------------------------------------------------
    | Main Action Logic
    |--------------------------------------------------------------------------
    */

    /**
     * Handle the OTP sending action.
     *
     * @param string $type The OTP type (login, update_password, etc.)
     * @param string $identifier Email or mobile number
     * @param array $userProvidedRequestData Additional data to store
     * @param User|null $user Optional user to associate with OTP
     * @return array{success: bool, message: string, otp?: Otp, expires_in_minutes?: int, channel?: string, status_code?: int, data?: array}
     */
    public function handle(
        string $type,
        string $identifier,
        array $userProvidedRequestData = [],
        ?User $user = null
    ): array {
        try {
            // Step 1: Check rate limiting (cooldown)
            if (!$this->otpService->canRequestOtp($identifier, $type)) {
                $remainingSeconds = $this->otpService->getRemainingCooldown($identifier, $type);
                
                return [
                    'success' => false,
                    'message' => "Please wait {$remainingSeconds} seconds before requesting a new OTP.",
                    'status_code' => 429,
                    'data' => [
                        'retry_after_seconds' => $remainingSeconds,
                    ],
                ];
            }

            // Step 2: Generate and store OTP
            $otp = $this->otpService->generate(
                type: $type,
                identifier: $identifier,
                userProvidedRequestData: $userProvidedRequestData,
                userId: $user?->id
            );

            // Step 3: Get the plain text OTP code for sending
            $code = $this->otpService->getPlainCode($otp);

            if (!$code) {
                Log::error('Failed to decrypt OTP code', ['otp_id' => $otp->id]);
                return [
                    'success' => false,
                    'message' => 'Failed to generate OTP. Please try again.',
                    'status_code' => 500,
                ];
            }

            // Step 4: Determine channel and send OTP
            $channel = $this->otpService->getChannelType($identifier);
            $sendResult = $this->sendOtp($identifier, $code, $type, $otp, $channel);

            if (!$sendResult['success']) {
                // Log the failure but still return success to user
                // (OTP was generated, just failed to send)
                Log::warning('OTP send failed', [
                    'otp_id' => $otp->id,
                    'channel' => $channel,
                    'error' => $sendResult['message'],
                ]);

                return [
                    'success' => false,
                    'message' => 'Failed to send OTP. Please try again.',
                    'status_code' => 500,
                ];
            }

            // Step 5: Return success response
            $expiryMinutes = $this->otpService->getExpiryMinutes($otp);

            Log::info('OTP sent successfully', [
                'otp_id' => $otp->id,
                'type' => $type,
                'channel' => $channel,
                'identifier_masked' => $this->maskIdentifier($identifier),
            ]);

            return [
                'success' => true,
                'message' => $this->getSuccessMessage($channel),
                'otp' => $otp,
                'expires_in_minutes' => $expiryMinutes,
                'channel' => $channel,
            ];

        } catch (\Exception $e) {
            Log::error('OTP send action failed', [
                'type' => $type,
                'identifier_masked' => $this->maskIdentifier($identifier),
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'An error occurred while sending OTP. Please try again.',
                'status_code' => 500,
                'error' => $e->getMessage(),
            ];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | OTP Delivery Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Send OTP via the appropriate channel.
     *
     * @param string $identifier Email or mobile
     * @param string $code The OTP code
     * @param string $type The OTP type
     * @param Otp $otp The OTP model
     * @param string $channel The delivery channel (email or sms)
     * @return array{success: bool, message: string}
     */
    protected function sendOtp(
        string $identifier,
        string $code,
        string $type,
        Otp $otp,
        string $channel
    ): array {
        return match ($channel) {
            'email' => $this->sendViaEmail($identifier, $code, $type, $otp),
            'sms' => $this->sendViaSms($identifier, $code, $type),
            default => [
                'success' => false,
                'message' => 'Unknown delivery channel.',
            ],
        };
    }

    /**
     * Send OTP via email using Laravel Notification.
     *
     * @param string $email The recipient email
     * @param string $code The OTP code
     * @param string $type The OTP type
     * @param Otp $otp The OTP model
     * @return array{success: bool, message: string}
     */
    protected function sendViaEmail(string $email, string $code, string $type, Otp $otp): array
    {
        try {
            $expiryMinutes = $this->otpService->getExpiryMinutes($otp);
            
            // Decode the encrypted data to pass to notification
            $data = [];
            if ($otp->data) {
                $data = $this->otpService->getRepository()->decryptData($otp->data) ?? [];
            }

            // Create the notification
            $notification = new OtpNotification($code, $type, $expiryMinutes, $data);

            // Send to email address using on-demand notification
            // (No User model required)
            Notification::route('mail', $email)->notify($notification);

            return [
                'success' => true,
                'message' => 'OTP email sent successfully.',
            ];

        } catch (\Exception $e) {
            Log::error('Email notification failed', [
                'email_masked' => $this->maskIdentifier($email),
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send email.',
            ];
        }
    }

    /**
     * Send OTP via SMS using SmsService.
     *
     * @param string $mobile The recipient mobile number
     * @param string $code The OTP code
     * @param string $type The OTP type
     * @return array{success: bool, message: string}
     */
    protected function sendViaSms(string $mobile, string $code, string $type): array
    {
        return $this->smsService->sendOtp($mobile, $code, $type);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Get success message based on channel.
     *
     * @param string $channel The delivery channel
     * @return string
     */
    protected function getSuccessMessage(string $channel): string
    {
        return match ($channel) {
            'email' => 'A verification code has been sent to your email address.',
            'sms' => 'A verification code has been sent to your mobile number.',
            default => 'A verification code has been sent.',
        };
    }

    /**
     * Mask identifier for logging (privacy).
     *
     * @param string $identifier Email or mobile
     * @return string
     */
    protected function maskIdentifier(string $identifier): string
    {
        if ($this->otpService->isEmail($identifier)) {
            // Mask email: j***@example.com
            $parts = explode('@', $identifier);
            if (count($parts) === 2) {
                $local = $parts[0];
                $domain = $parts[1];
                $maskedLocal = substr($local, 0, 1) . str_repeat('*', max(strlen($local) - 1, 3));
                return $maskedLocal . '@' . $domain;
            }
        }

        // Mask mobile: +91****1234
        $length = strlen($identifier);
        if ($length > 4) {
            return substr($identifier, 0, 3) . str_repeat('*', $length - 7) . substr($identifier, -4);
        }

        return str_repeat('*', $length);
    }

    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    */

    /**
     * Get the route name for this action.
     *
     * @return string
     */
    public static function getRouteName(): string
    {
        return 'otp.send';
    }

    /**
     * Get the middleware for this action.
     *
     * @return array
     */
    public function getMiddleware(): array
    {
        return [
            'throttle:otp', // Rate limit OTP requests
        ];
    }
}
