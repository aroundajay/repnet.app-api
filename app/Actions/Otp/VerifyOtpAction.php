<?php

namespace App\Actions\Otp;

use App\Http\Requests\Otp\VerifyOtpRequest;
use App\Models\Otp;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Verify OTP Action
 * 
 * Orchestrates the OTP verification process:
 * 1. Validates the request (type, identifier, code)
 * 2. Verifies the OTP code via OtpService
 * 3. Returns verification result with appropriate status
 * 
 * Uses Laravel Actions pattern for clean, single-purpose code.
 */
class VerifyOtpAction
{
    use AsAction;

    /**
     * Create a new action instance.
     */
    public function __construct(
        protected OtpService $otpService
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
     * @param VerifyOtpRequest $request The validated request
     * @return array
     */
    public function asController(VerifyOtpRequest $request): array
    {
        $type = $request->input('type');
        $identifier = $request->input('identifier');
        $code = $request->input('code');

        // Execute the action
        return $this->handle($type, $identifier, $code);
    }

    /**
     * Convert the action result to a JSON response.
     *
     * @param array $data The result data from handle()
     * @return JsonResponse
     */
    public function jsonResponse(array $data): JsonResponse
    {
        // execute the callback if it exists
        if ($data['success'] && $data['callback']) {
            $data = app($data['callback'])->handle($data['callback_data']);
        }

        return response()->json($data, $data['status_code'] ?? 200);
    }

    /*
    |--------------------------------------------------------------------------
    | Main Action Logic
    |--------------------------------------------------------------------------
    */

    /**
     * Handle the OTP verification action.
     *
     * @param string $type The OTP type (login, update_password, etc.)
     * @param string $identifier Email or mobile number
     * @param string $code The OTP code to verify
     * @return array{success: bool, message: string, status_code?: int, data?: array}
     */
    public function handle(string $type, string $identifier, string $code): array
    {
        try {
            // Step 1: Verify the OTP via service
            $result = $this->otpService->verify($identifier, $type, $code);

            // Step 2: Log the verification attempt
            $this->logVerificationAttempt($type, $identifier, $result['success']);

            // Step 3: Build and return the response
            if ($result['success']) {
                return $this->buildSuccessResponse($result['otp'], $type);
            }

            return $this->buildFailureResponse($result);

        } catch (\Exception $e) {
            Log::error('OTP verification action failed', [
                'type' => $type,
                'identifier_masked' => $this->maskIdentifier($identifier),
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'An error occurred during verification. Please try again.',
                'status_code' => 500,
            ];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Response Builders
    |--------------------------------------------------------------------------
    */

    /**
     * Build success response after OTP verification.
     *
     * @param Otp $otp The verified OTP model
     * @param string $type The OTP type
     * @return array
     */
    protected function buildSuccessResponse(Otp $otp, string $type): array
    {
        // Get decrypted data for response (if needed)
        $otpData = $this->getDecryptedOtpData($otp);

        return [
            'success' => true,
            'message' => $this->getSuccessMessage($type),
            'status_code' => 200,
            'callback' => $otpData['callback'] ?? false,
            'callback_data' => $otpData['value'] ?? [],
        ];
    }

    /**
     * Build failure response for OTP verification.
     *
     * @param array $result The verification result from service
     * @return array
     */
    protected function buildFailureResponse(array $result): array
    {
        $statusCode = $this->determineFailureStatusCode($result);

        return [
            'success' => false,
            'message' => $result['message'],
            'status_code' => $statusCode,
            'data' => $this->buildFailureData($result),
        ];
    }

    /**
     * Determine appropriate HTTP status code for failure.
     *
     * @param array $result The verification result
     * @return int
     */
    protected function determineFailureStatusCode(array $result): int
    {
        // No active OTP found
        if ($result['otp'] === null) {
            return 404;
        }

        // Max attempts exceeded
        $maxAttempts = config('otp.security.max_attempts', 5);
        if ($result['otp']->failed_attempts >= $maxAttempts) {
            return 429;
        }

        // Invalid code (but attempts remaining)
        return 422;
    }

    /**
     * Build additional data for failure response.
     *
     * @param array $result The verification result
     * @return array|null
     */
    protected function buildFailureData(array $result): ?array
    {
        if ($result['otp'] === null) {
            return null;
        }

        $maxAttempts = config('otp.security.max_attempts', 5);
        $remainingAttempts = max(0, $maxAttempts - $result['otp']->failed_attempts);

        return [
            'attempts_remaining' => $remainingAttempts,
            'max_attempts' => $maxAttempts,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Get success message based on OTP type.
     *
     * @param string $type The OTP type
     * @return string
     */
    protected function getSuccessMessage(string $type): string
    {
        return match ($type) {
            'signup' => 'Account verified successfully. You can now complete your registration.',
            'login' => 'Login verified successfully.',
            'update_password' => 'Code verified. You can now reset your password.',
            'update_email' => 'Email verified successfully.',
            'update_mobile' => 'Mobile number verified successfully.',
            default => 'Verification successful.',
        };
    }

    /**
     * Get decrypted OTP data.
     *
     * @param Otp $otp The OTP model
     * @return array
     */
    protected function getDecryptedOtpData(Otp $otp): array
    {
        if (!$otp->data) {
            return [];
        }

        return $this->otpService->getRepository()->decryptData($otp->data) ?? [];
    }

    /**
     * Log the verification attempt.
     *
     * @param string $type The OTP type
     * @param string $identifier The identifier
     * @param bool $success Whether verification succeeded
     */
    protected function logVerificationAttempt(string $type, string $identifier, bool $success): void
    {
        $logData = [
            'type' => $type,
            'identifier_masked' => $this->maskIdentifier($identifier),
            'success' => $success,
        ];

        if ($success) {
            Log::info('OTP verified successfully', $logData);
        } else {
            Log::warning('OTP verification failed', $logData);
        }
    }

    /**
     * Mask identifier for logging (privacy).
     *
     * @param string $identifier Email or mobile
     * @return string
     */
    protected function maskIdentifier(string $identifier): string
    {
        // Check if email
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
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
        return 'otp.verify';
    }

    /**
     * Get the middleware for this action.
     *
     * @return array
     */
    public function getMiddleware(): array
    {
        return [
            'throttle:otp', // Rate limit OTP verification attempts
        ];
    }
}
