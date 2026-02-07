<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * User model representing application users.
 * 
 * Uses UUID primary key for better security and distribution.
 * Supports OTP-based authentication with optional password.
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasUuids, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'mobile',
        'email',
        'password',
        'email_verified_at',
        'mobile_verified_at',
        'name',
        'avatar',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'mobile_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get all OTPs for this user.
     */
    public function otps(): HasMany
    {
        return $this->hasMany(Otp::class);
    }

    /**
     * Get all files uploaded by this user.
     */
    public function uploadedFiles(): HasMany
    {
        return $this->hasMany(File::class, 'uploaded_by');
    }

    /**
     * Get all gyms created by this user.
     */
    public function ownedGyms(): HasMany
    {
        return $this->hasMany(Gym::class);
    }

    /**
     * Get all gym memberships for this user.
     */
    public function gymMemberships(): HasMany
    {
        return $this->hasMany(GymUser::class);
    }

    /**
     * Get all notice posts by this user.
     */
    public function noticePosts(): HasMany
    {
        return $this->hasMany(NoticePost::class, 'posted_by');
    }

    /**
     * Get all challenge submissions by this user.
     */
    public function challengeSubmissions(): HasMany
    {
        return $this->hasMany(ChallengeSubmission::class);
    }

    /**
     * Get all partner requests by this user.
     */
    public function partnerRequests(): HasMany
    {
        return $this->hasMany(PartnerRequest::class);
    }

    /**
     * Get all messages sent by this user.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
