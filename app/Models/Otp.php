<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * OTP model for one-time password authentication.
 * 
 * Supports different OTP types: login, update_password, update_email.
 * Tracks sent and expiration timestamps for security.
 */
class Otp extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'type',
        'data',
        'otp',
        'identifier',
        'failed_attempts',
        'last_failed_attempt_at',
        'succeeded_at',
        'sent_at',
        'expired_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'data', // Never expose data in API responses
        'otp', // Never expose OTP in API responses
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'expired_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the user that owns this OTP.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if the OTP has expired.
     */
    public function isExpired(): bool
    {
        return $this->expired_at->isPast();
    }

    /**
     * Check if the OTP is valid (not expired and matches).
     * Decrypts stored OTP and compares with provided code.
     */
    public function isValid(string $code): bool
    {
        if ($this->isExpired()) {
            return false;
        }

        try {
            $decryptedOtp = \Illuminate\Support\Facades\Crypt::decryptString($this->otp);
            return $decryptedOtp === $code;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if max failed attempts has been exceeded.
     */
    public function hasExceededMaxAttempts(): bool
    {
        $maxAttempts = config('otp.security.max_attempts', 5);
        return $this->failed_attempts >= $maxAttempts;
    }

    /**
     * Check if the OTP has already been used.
     */
    public function isUsed(): bool
    {
        return $this->succeeded_at !== null;
    }
}
