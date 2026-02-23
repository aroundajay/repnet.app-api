<?php

/**
 * API V1 Routes
 *
 * All routes in this file are prefixed with /api/v1
 * and use the 'api' middleware group.
 */

use App\Actions\Auth\LoginAction;
use App\Actions\File\UploadFileAction;
use App\Actions\Gym\CreateGymAction;
use App\Actions\Gym\InviteGymUserAction;
use App\Actions\Gym\ListGymAction;
use App\Actions\Gym\ListGymUsersAction;
use App\Actions\Gym\RequestGymJoinAction;
use App\Actions\Gym\UpdateGymAction;
use App\Actions\Gym\UpdateGymInviteStatusAction;
use App\Actions\Gym\UpdateGymJoinAction;
use App\Actions\Otp\SendOtpAction;
use App\Actions\Otp\VerifyOtpAction;
use App\Actions\User\UpdateUserAction;
use App\Actions\Workout\ListWorkoutTypeAction;
use App\Actions\Amenity\ListAmenityAction;
use App\Services\GymService;

use Illuminate\Support\Facades\Route;
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
| Gym Routes (Public - for listing public gyms)
|--------------------------------------------------------------------------
*/
Route::prefix('gyms')->group(function () {
    Route::get('', ListGymAction::class)->name('gyms.list');
    Route::get('{gymId}', function () {
        return [
            'success' => true,
            'status_code' => 200,
            'message' => 'User fetched successfully',
            'data' => [
                'gym' => app()->make(GymService::class)->findGym(request()->gymId, ['files', 'amenities', 'workoutTypes']),
            ],
        ];
    })->name('gyms.show');
});

/*
|--------------------------------------------------------------------------
| Workout Types Routes
|--------------------------------------------------------------------------
*/
Route::prefix('workout-types')->group(function () {
    Route::get('', ListWorkoutTypeAction::class)->name('workout-types.list');
});

/*
|--------------------------------------------------------------------------
| Permissions Routes (Public - for getting permissions)
|--------------------------------------------------------------------------
*/
Route::get('app-permissions', function () {
    return [
        'success' => true,
        'status_code' => 200,
        'message' => 'Permissions fetched successfully',
        'data' => config('apppermissions'),
    ];
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
                'data' => request()->user()->fresh(['gyms.files']),
            ];
        });
        Route::put('', UpdateUserAction::class)->name('user.update');
    });

    // Gym routes
    Route::prefix('gyms')->group(function () {
        // Gym routes - create gym (current user becomes owner)
        Route::post('', CreateGymAction::class)->name('gyms.create');

        // Gym routes - update gym
        Route::patch('{gymId}', UpdateGymAction::class)->name('gyms.update');

        // Gym routes - invite user to gym
        Route::post('{gymId}/invite', InviteGymUserAction::class)->name('gyms.invite');

        // Gym routes - update gym invite status
        Route::put('{gymId}/invite/{userId}/status', UpdateGymInviteStatusAction::class)->name('gyms.update.invite.status');

        // Gym routes - request to join gym
        Route::post('{gymId}/request-join', RequestGymJoinAction::class)->name('gyms.request.join');

        // Gym routes - update gym join status
        Route::put('{gymId}/request-join/{userId}/status', UpdateGymJoinAction::class)->name('gyms.update.join.status');

        // Gym routes - list gym members with optional search (q) and status filter
        Route::get('{gymId}/users', ListGymUsersAction::class)->name('gyms.users.list');
    });

    // File routes
    Route::prefix('files')->group(function () {
        Route::post('', UploadFileAction::class)->name('files.upload');
    });

    // Amenities
    Route::prefix('amenities')->group(function () {
        Route::get('', ListAmenityAction::class)->name('amenities.list');
    });
});
