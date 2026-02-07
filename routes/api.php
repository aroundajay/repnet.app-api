<?php

/**
 * API V1 Routes
 *
 * All routes in this file are prefixed with /api/v1
 * and use the 'api' middleware group.
 */

use Illuminate\Support\Facades\Route;
use App\Actions\User\UpdateUserAction;
use App\Actions\Otp\SendOtpAction;
use App\Actions\Otp\VerifyOtpAction;
use App\Actions\Auth\LoginAction;
use App\Actions\Gym\CreateGymAction;
use App\Actions\Gym\InviteGymUserAction;
use App\Actions\Gym\UpdateGymInviteStatusAction;
/*
|--------------------------------------------------------------------------
| Public Routes (No Authentication Required)
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| OTP Routes (Public - for authentication)
|--------------------------------------------------------------------------
*/
Route::prefix('otp')->group(function () {
    // Send OTP to email or mobile
    Route::post('/send', SendOtpAction::class)->name('otp.send');

    // Send OTP to authenticated user
    Route::post('/send-authenticated', SendOtpAction::class)->name('otp.send.authenticated')->middleware('auth:sanctum');

    // Verify OTP code
    Route::post('/verify', VerifyOtpAction::class)->name('otp.verify');
});

/*
|--------------------------------------------------------------------------
| Auth Routes (Public - for authentication)
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    // Login
    Route::post('/login', LoginAction::class)->name('auth.login');
});

/*
|--------------------------------------------------------------------------
| Protected Routes (Sanctum Authentication Required)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    // User routes
    Route::prefix('user')->group(function () {
        Route::get('', function () {
            return [
                'success' => true,
                'status_code' => 200,
                'message' => 'User fetched successfully',
                'data' => request()->user(),
            ];
        });
        Route::put('', UpdateUserAction::class)->name('user.update');
    });

    // Gym routes
    Route::prefix('gyms')->group(function () {
        // Gym routes - create gym (current user becomes owner)
        Route::post('', CreateGymAction::class)->name('gyms.create');

        // Gym routes - invite user to gym
        Route::post('{gymId}/invite', InviteGymUserAction::class)->name('gyms.invite');

        // Gym routes - update gym invite status
        Route::put('{gymId}/invite/{userId}/status', UpdateGymInviteStatusAction::class)->name('gyms.update.invite.status');
    });

    // Add your protected API routes here
});
