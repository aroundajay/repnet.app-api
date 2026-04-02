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
use App\Actions\User\GetUserAction;
use App\Actions\User\GetUserPostsAction;
use App\Actions\User\UpdateUserAction;
use App\Actions\Workout\ListWorkoutTypeAction;
use App\Actions\Amenity\ListAmenityAction;
use App\Actions\Notification\ListNotificationAction;
use App\Actions\Notification\MarkNotificationReadAction;
use App\Actions\Notification\MarkAllNotificationsReadAction;
use App\Actions\Feed\UserFeedAction;
use App\Actions\Message\CreateCommentMessageAction;
use App\Actions\Message\CreateMessageAction;
use App\Actions\Message\ListMessageAction;
use App\Actions\Message\GetMessageAction;
use App\Actions\Message\ToggleMessageReactionAction;
use App\Actions\Message\DeleteMessageAction;
use App\Actions\Message\ListMessageReactedUsersAction;
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
    Route::prefix('users')->group(function () {
        // Get any user's profile by their ID
        Route::get('{userId}', GetUserAction::class)->name('users.show');

        // Get user posts by user ID
        Route::get('{userId}/posts', GetUserPostsAction::class)->name('users.posts.list');
    });

    Route::prefix('user')->group(function () {
        Route::get('', function () {
            return [
                'success' => true,
                'status_code' => 200,
                'message' => 'User fetched successfully',
                'data' => request()->user()->fresh(['gyms.files', 'gyms.messageThread', 'files', 'notifications']),
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

    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('', ListNotificationAction::class)->name('notifications.list');
        Route::patch('read-all', MarkAllNotificationsReadAction::class)->name('notifications.read-all');
        Route::patch('{notificationId}/read', MarkNotificationReadAction::class)->name('notifications.read');
    });

    // messages
    Route::prefix('messages')->group(function () {
        // Send a new message to a thread (with optional file attachments)
        Route::post('threads/{threadId}/messages', CreateMessageAction::class)->name('messages.create');

        // Send a comment message to a message
        Route::post('{messageId}/comments', CreateCommentMessageAction::class)->name('comments.create');

        // List messages in a thread with cursor pagination (newest first)
        Route::get('threads/{threadId}/messages', ListMessageAction::class)->name('messages.list');

        // Get a message by ID
        Route::get('threads/{threadId}/messages/{messageId}', GetMessageAction::class)->name('messages.show');

        // Toggle a reaction for a message
        Route::patch('threads/{threadId}/messages/{messageId}/react', ToggleMessageReactionAction::class)->name('messages.toggle-reaction');

        Route::get('threads/{threadId}/messages/{messageId}/reacted-users', ListMessageReactedUsersAction::class)->name('messages.reacted-users.list');

        // Delete a message and all of its nested thread + child messages recursively
        Route::delete('threads/{threadId}/messages/{messageId}', DeleteMessageAction::class)->name('messages.delete');
    });

    // user feed
    Route::prefix('feed')->group(function () {
        Route::get('', UserFeedAction::class)->name('feed.list');
    });
});
