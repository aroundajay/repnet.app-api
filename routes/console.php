<?php

use App\Jobs\CleanupExpiredOtpsJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Commands
|--------------------------------------------------------------------------
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
|
| Here you may define all of your scheduled tasks. Laravel's task scheduler
| allows you to fluently express your command schedule within PHP itself.
|
*/

// Cleanup expired and succeeded OTPs daily at midnight
// This permanently removes stale OTP records to keep the database clean
Schedule::job(new CleanupExpiredOtpsJob())
    ->daily()
    ->at('00:00')
    ->name('cleanup-expired-otps')
    ->description('Force delete expired and succeeded OTP records')
    ->withoutOverlapping()
    ->onOneServer();
