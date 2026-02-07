<?php

/**
 * OTP (One-Time Password) Configuration
 * 
 * This file contains all configuration settings for OTP generation,
 * expiration, rate limiting, and type-specific settings.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | OTP Code Settings
    |--------------------------------------------------------------------------
    |
    | Configure the OTP code format and length.
    | - length: Number of digits in the OTP code
    | - expiry_minutes: How long the OTP is valid
    |
    */

    'code' => [
        'length' => env('OTP_CODE_LENGTH', 6),
        'expiry_minutes' => env('OTP_EXPIRY_MINUTES', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Rate limiting and security settings for OTP.
    | - max_attempts: Maximum verification attempts before lockout
    | - resend_cooldown_seconds: Minimum time between OTP resends
    | - lockout_minutes: Lockout duration after max attempts exceeded
    |
    */

    'security' => [
        'max_attempts' => env('OTP_MAX_ATTEMPTS', 5),
        'resend_cooldown_seconds' => env('OTP_RESEND_COOLDOWN', 60),
        'lockout_minutes' => env('OTP_LOCKOUT_MINUTES', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | OTP Types Configuration
    |--------------------------------------------------------------------------
    |
    | Define different OTP types and their specific settings.
    | Each type can have custom expiry, callback URL, and data template.
    |
    */

    'types' => [
        'signup' => [
            'expiry_minutes' => 5,
            'callback' => \App\Actions\User\CreateUserAction::class,
        ],
        'login' => [
            'expiry_minutes' => 5,
            'callback' => \App\Actions\Auth\LoginAction::class,
        ],
        'update_password' => [
            'expiry_minutes' => 15,
            'callback' => \App\Actions\User\UpdateUserAction::class,
        ],
        'update_email' => [
            'expiry_minutes' => 30,
            'callback' => \App\Actions\User\UpdateUserAction::class,
        ],
        'update_mobile' => [
            'expiry_minutes' => 10,
            'callback' => \App\Actions\User\UpdateUserAction::class,
        ],

        'invite_to_gym' => [
            'expiry_minutes' => 60,
            'callback' => \App\Actions\Gym\UpdateGymInviteStatusAction::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS Service Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the SMS service provider settings.
    | Supported providers: twilio, nexmo, custom
    |
    */

    'sms' => [
        'provider' => env('SMS_PROVIDER', 'twilio'),
        'from' => env('SMS_FROM_NUMBER'),
        
        // Twilio settings
        'twilio' => [
            'sid' => env('TWILIO_SID'),
            'token' => env('TWILIO_TOKEN'),
            'from' => env('TWILIO_FROM_NUMBER'),
        ],
        
        // Custom API settings (for generic HTTP-based SMS providers)
        'custom' => [
            'api_url' => env('SMS_API_URL'),
            'api_key' => env('SMS_API_KEY'),
            'api_secret' => env('SMS_API_SECRET'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Message Templates
    |--------------------------------------------------------------------------
    |
    | Templates for OTP messages. Use :code placeholder for the OTP code.
    |
    */

    'messages' => [
        'sms' => 'Your :app_name verification code is :code. Valid for :expiry minutes. Do not share this code.',
        'email_subject' => 'Your verification code for :app_name',
    ],

];
