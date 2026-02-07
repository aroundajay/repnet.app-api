<?php

namespace App\Repositories;

use App\Models\Otp;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

/**
 * OTP Repository
 * 
 * Handles all database operations for OTP model.
 * Encapsulates data access logic and provides clean interface.
 */
class OtpRepository
{
    /*
    |--------------------------------------------------------------------------
    | Create Operations
    |--------------------------------------------------------------------------
    */

    /**
     * Create a new OTP record with encrypted data.
     *
     * @param array $data The OTP data to store
     * @return Otp The created OTP model
     */
    public function create(array $data): Otp
    {
        // Encrypt the OTP code before storing
        $data['otp'] = $this->encryptOtp($data['otp']);

        // Encrypt the data field if present
        if (isset($data['data'])) {
            $data['data'] = $this->encryptData($data['data']);
        }

        return Otp::create($data);
    }

    /*
    |--------------------------------------------------------------------------
    | Read Operations
    |--------------------------------------------------------------------------
    */

    /**
     * Find an OTP by its ID.
     *
     * @param string $id The OTP UUID
     * @return Otp|null
     */
    public function findById(string $id): ?Otp
    {
        return Otp::find($id);
    }

    /**
     * Find the latest active OTP for an identifier and type.
     *
     * @param string $identifier Email or mobile number
     * @param string $type The OTP type (login, update_password, etc.)
     * @return Otp|null
     */
    public function findLatestActive(string $identifier, string $type): ?Otp
    {
        return Otp::where('identifier', $identifier)
            ->where('type', $type)
            ->where('expired_at', '>', now())
            ->whereNull('succeeded_at')
            ->latest('sent_at')
            ->first();
    }

    /**
     * Find OTP by identifier and type for verification.
     *
     * @param string $identifier Email or mobile number
     * @param string $type The OTP type
     * @param string $code The plain text OTP code to verify
     * @return Otp|null
     */
    public function findForVerification(string $identifier, string $type, string $code): ?Otp
    {
        // Get the latest active OTP
        $otp = $this->findLatestActive($identifier, $type);

        if (!$otp) {
            return null;
        }

        // Verify the code matches
        if (!$this->verifyOtp($code, $otp->otp)) {
            return null;
        }

        return $otp;
    }

    /**
     * Check if user can request a new OTP (cooldown check).
     *
     * @param string $identifier Email or mobile number
     * @param string $type The OTP type
     * @param int $cooldownSeconds Minimum seconds between requests
     * @return bool
     */
    public function canRequestNewOtp(string $identifier, string $type, int $cooldownSeconds): bool
    {
        $lastOtp = Otp::where('identifier', $identifier)
            ->where('type', $type)
            ->latest('sent_at')
            ->first();

        if (!$lastOtp) {
            return true;
        }

        // Check if cooldown period has passed
        return $lastOtp->sent_at->addSeconds($cooldownSeconds)->isPast();
    }

    /**
     * Get remaining cooldown seconds for an identifier.
     *
     * @param string $identifier Email or mobile number
     * @param string $type The OTP type
     * @param int $cooldownSeconds Total cooldown period
     * @return int Remaining seconds (0 if can request now)
     */
    public function getRemainingCooldown(string $identifier, string $type, int $cooldownSeconds): int
    {
        $lastOtp = Otp::where('identifier', $identifier)
            ->where('type', $type)
            ->latest('sent_at')
            ->first();

        if (!$lastOtp) {
            return 0;
        }

        $nextAllowedTime = $lastOtp->sent_at->addSeconds($cooldownSeconds);

        if ($nextAllowedTime->isPast()) {
            return 0;
        }

        return now()->diffInSeconds($nextAllowedTime);
    }

    /*
    |--------------------------------------------------------------------------
    | Update Operations
    |--------------------------------------------------------------------------
    */

    /**
     * Mark OTP as successfully verified.
     *
     * @param Otp $otp The OTP to mark as succeeded
     * @return bool
     */
    public function markAsSucceeded(Otp $otp): bool
    {
        return $otp->update([
            'succeeded_at' => now(),
        ]);
    }

    /**
     * Increment failed attempts for an OTP.
     *
     * @param Otp $otp The OTP to update
     * @return bool
     */
    public function incrementFailedAttempts(Otp $otp): bool
    {
        return $otp->update([
            'failed_attempts' => $otp->failed_attempts + 1,
            'last_failed_attempt_at' => now(),
        ]);
    }

    /**
     * Invalidate all active OTPs for an identifier and type.
     * Used when a new OTP is generated to invalidate old ones.
     *
     * @param string $identifier Email or mobile number
     * @param string $type The OTP type
     * @return int Number of records updated
     */
    public function invalidatePreviousOtps(string $identifier, string $type): int
    {
        return Otp::where('identifier', $identifier)
            ->where('type', $type)
            ->whereNull('succeeded_at')
            ->where('expired_at', '>', now())
            ->update([
                'expired_at' => now(),
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Operations
    |--------------------------------------------------------------------------
    */

    /**
     * Soft delete expired OTPs older than specified days.
     * Useful for cleanup jobs.
     *
     * @param int $days Number of days
     * @return int Number of records deleted
     */
    public function deleteExpiredOlderThan(int $days): int
    {
        return Otp::where('expired_at', '<', now()->subDays($days))
            ->delete();
    }

    /**
     * Force delete all stale OTP records.
     * Stale OTPs are those that are expired OR have been successfully used.
     * This permanently removes records from the database.
     *
     * @return array{expired: int, succeeded: int, total: int} Deletion counts
     */
    public function forceDeleteStaleOtps(): array
    {
        // Count expired OTPs (including soft deleted)
        $expiredCount = Otp::withTrashed()
            ->where('expired_at', '<', now())
            ->count();

        // Count succeeded OTPs (including soft deleted)
        $succeededCount = Otp::withTrashed()
            ->whereNotNull('succeeded_at')
            ->where('expired_at', '>=', now()) // Only count non-expired succeeded to avoid double counting
            ->count();

        // Force delete expired OTPs
        Otp::withTrashed()
            ->where('expired_at', '<', now())
            ->forceDelete();

        // Force delete succeeded OTPs (those with succeeded_at value)
        Otp::withTrashed()
            ->whereNotNull('succeeded_at')
            ->forceDelete();

        return [
            'expired' => $expiredCount,
            'succeeded' => $succeededCount,
            'total' => $expiredCount + $succeededCount,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Encryption Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Encrypt the OTP code for storage.
     *
     * @param string $otp Plain text OTP
     * @return string Encrypted OTP
     */
    protected function encryptOtp(string $otp): string
    {
        return Crypt::encryptString($otp);
    }

    /**
     * Verify a plain text OTP against an encrypted one.
     *
     * @param string $plainOtp Plain text OTP to verify
     * @param string $encryptedOtp Encrypted OTP from database
     * @return bool
     */
    protected function verifyOtp(string $plainOtp, string $encryptedOtp): bool
    {
        try {
            $decrypted = Crypt::decryptString($encryptedOtp);
            return $plainOtp === $decrypted;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Encrypt data array for storage.
     *
     * @param array $data Data to encrypt
     * @return string Encrypted JSON string
     */
    protected function encryptData(array $data): string
    {
        return Crypt::encryptString(json_encode($data));
    }

    /**
     * Decrypt data from storage.
     *
     * @param string $encryptedData Encrypted data string
     * @return array|null
     */
    public function decryptData(string $encryptedData): ?array
    {
        try {
            $decrypted = Crypt::decryptString($encryptedData);
            return json_decode($decrypted, true);
        } catch (\Exception $e) {
            return null;
        }
    }
}
