<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SMS Service
 * 
 * Handles SMS delivery through various providers.
 * Supports Twilio and custom HTTP-based SMS providers.
 */
class SmsService
{
    /**
     * The SMS provider to use.
     */
    protected string $provider;

    /**
     * Create a new SMS service instance.
     */
    public function __construct()
    {
        $this->provider = config('otp.sms.provider', 'twilio');
    }

    /*
    |--------------------------------------------------------------------------
    | Public Interface
    |--------------------------------------------------------------------------
    */

    /**
     * Send an SMS message.
     *
     * @param string $to The recipient phone number
     * @param string $message The message content
     * @return array{success: bool, message: string, provider: string, reference?: string}
     */
    public function send(string $to, string $message): array
    {
        // Normalize the phone number
        $to = $this->normalizePhoneNumber($to);

        // Validate phone number
        if (!$this->isValidPhoneNumber($to)) {
            return [
                'success' => false,
                'message' => 'Invalid phone number format.',
                'provider' => $this->provider,
            ];
        }

        // Log the SMS attempt (don't log full message in production)
        Log::info('Attempting to send SMS', [
            'provider' => $this->provider,
            'to' => $this->maskPhoneNumber($to),
        ]);

        // Route to appropriate provider
        return match ($this->provider) {
            'twilio' => $this->sendViaTwilio($to, $message),
            'custom' => $this->sendViaCustomApi($to, $message),
            'log' => $this->sendViaLog($to, $message), // For testing
            default => $this->sendViaLog($to, $message),
        };
    }

    /**
     * Send OTP SMS message.
     * Convenience method with OTP-specific formatting.
     *
     * @param string $to The recipient phone number
     * @param string $code The OTP code
     * @param string $type The OTP type
     * @return array
     */
    public function sendOtp(string $to, string $code, string $type): array
    {
        $message = $this->formatOtpMessage($code, $type);

        return $this->send($to, $message);
    }

    /*
    |--------------------------------------------------------------------------
    | Provider Implementations
    |--------------------------------------------------------------------------
    */

    /**
     * Send SMS via Twilio.
     *
     * @param string $to Recipient phone number
     * @param string $message Message content
     * @return array
     */
    protected function sendViaTwilio(string $to, string $message): array
    {
        $sid = config('otp.sms.twilio.sid');
        $token = config('otp.sms.twilio.token');
        $from = config('otp.sms.twilio.from');

        // Validate Twilio configuration
        if (!$sid || !$token || !$from) {
            Log::error('Twilio configuration missing');
            return [
                'success' => false,
                'message' => 'SMS service not configured properly.',
                'provider' => 'twilio',
            ];
        }

        try {
            $response = Http::withBasicAuth($sid, $token)
                ->asForm()
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                    'To' => $to,
                    'From' => $from,
                    'Body' => $message,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('SMS sent successfully via Twilio', [
                    'sid' => $data['sid'] ?? null,
                    'to' => $this->maskPhoneNumber($to),
                ]);

                return [
                    'success' => true,
                    'message' => 'SMS sent successfully.',
                    'provider' => 'twilio',
                    'reference' => $data['sid'] ?? null,
                ];
            }

            // Handle Twilio error response
            $error = $response->json();
            Log::error('Twilio SMS failed', [
                'error' => $error['message'] ?? 'Unknown error',
                'code' => $error['code'] ?? null,
            ]);

            return [
                'success' => false,
                'message' => $error['message'] ?? 'Failed to send SMS.',
                'provider' => 'twilio',
            ];
        } catch (\Exception $e) {
            Log::error('Twilio SMS exception', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'SMS service temporarily unavailable.',
                'provider' => 'twilio',
            ];
        }
    }

    /**
     * Send SMS via custom HTTP API.
     * Customize this method based on your SMS provider's API.
     *
     * @param string $to Recipient phone number
     * @param string $message Message content
     * @return array
     */
    protected function sendViaCustomApi(string $to, string $message): array
    {
        $apiUrl = config('otp.sms.custom.api_url');
        $apiKey = config('otp.sms.custom.api_key');
        $apiSecret = config('otp.sms.custom.api_secret');

        // Validate custom API configuration
        if (!$apiUrl || !$apiKey) {
            Log::error('Custom SMS API configuration missing');
            return [
                'success' => false,
                'message' => 'SMS service not configured properly.',
                'provider' => 'custom',
            ];
        }

        try {
            // Customize payload based on your SMS provider's requirements
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'X-Api-Secret' => $apiSecret,
            ])->post($apiUrl, [
                'to' => $to,
                'message' => $message,
                'from' => config('otp.sms.from'),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('SMS sent successfully via custom API', [
                    'to' => $this->maskPhoneNumber($to),
                ]);

                return [
                    'success' => true,
                    'message' => 'SMS sent successfully.',
                    'provider' => 'custom',
                    'reference' => $data['id'] ?? $data['message_id'] ?? null,
                ];
            }

            Log::error('Custom API SMS failed', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send SMS.',
                'provider' => 'custom',
            ];
        } catch (\Exception $e) {
            Log::error('Custom SMS API exception', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'SMS service temporarily unavailable.',
                'provider' => 'custom',
            ];
        }
    }

    /**
     * Send SMS via log (for testing/development).
     * Logs the message instead of actually sending.
     *
     * @param string $to Recipient phone number
     * @param string $message Message content
     * @return array
     */
    protected function sendViaLog(string $to, string $message): array
    {
        Log::channel('daily')->info('SMS (Log Mode)', [
            'to' => $to,
            'message' => $message,
            'provider' => 'log',
        ]);

        return [
            'success' => true,
            'message' => 'SMS logged successfully (test mode).',
            'provider' => 'log',
            'reference' => 'log-' . uniqid(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Normalize phone number to E.164 format.
     *
     * @param string $phone The phone number to normalize
     * @return string
     */
    protected function normalizePhoneNumber(string $phone): string
    {
        // Remove all non-digit characters except leading +
        $cleaned = preg_replace('/[^\d+]/', '', $phone);

        // Ensure it starts with + if it has country code
        if (!str_starts_with($cleaned, '+') && strlen($cleaned) > 10) {
            $cleaned = '+' . $cleaned;
        }

        return $cleaned;
    }

    /**
     * Validate phone number format.
     *
     * @param string $phone The phone number to validate
     * @return bool
     */
    protected function isValidPhoneNumber(string $phone): bool
    {
        // E.164 format: +[country code][number], 7-15 digits total
        return preg_match('/^\+?[1-9]\d{6,14}$/', $phone) === 1;
    }

    /**
     * Mask phone number for logging (privacy).
     *
     * @param string $phone The phone number to mask
     * @return string
     */
    protected function maskPhoneNumber(string $phone): string
    {
        $length = strlen($phone);
        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        // Show first 3 and last 2 characters
        return substr($phone, 0, 3) . str_repeat('*', $length - 5) . substr($phone, -2);
    }

    /**
     * Format OTP message using template.
     *
     * @param string $code The OTP code
     * @param string $type The OTP type
     * @return string
     */
    protected function formatOtpMessage(string $code, string $type): string
    {
        $template = config('otp.messages.sms');
        $typeConfig = config("otp.types.{$type}", []);
        $expiryMinutes = $typeConfig['expiry_minutes'] ?? config('otp.code.expiry_minutes', 10);

        return str_replace(
            [':app_name', ':code', ':expiry'],
            [config('app.name'), $code, $expiryMinutes],
            $template
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Configuration Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Set the SMS provider dynamically.
     *
     * @param string $provider The provider name
     * @return self
     */
    public function setProvider(string $provider): self
    {
        $this->provider = $provider;
        return $this;
    }

    /**
     * Get the current provider.
     *
     * @return string
     */
    public function getProvider(): string
    {
        return $this->provider;
    }
}
