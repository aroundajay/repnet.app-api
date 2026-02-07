<?php

namespace App\Jobs;

use App\Repositories\OtpRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Cleanup Expired OTPs Job
 * 
 * Scheduled job to permanently delete stale OTP records.
 * Removes OTPs that are either:
 * - Expired (expired_at is in the past)
 * - Successfully used (succeeded_at has a value)
 * 
 * This helps keep the database clean and maintains performance.
 * Scheduled to run daily via Laravel's task scheduler.
 */
class CleanupExpiredOtpsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     * Force deletes all expired and succeeded OTP records.
     *
     * @param OtpRepository $otpRepository
     * @return void
     */
    public function handle(OtpRepository $otpRepository): void
    {
        Log::info('Starting OTP cleanup job');

        try {
            // Force delete stale OTPs via repository
            $result = $otpRepository->forceDeleteStaleOtps();

            // Log the results
            Log::info('OTP cleanup completed successfully', [
                'expired_deleted' => $result['expired'],
                'succeeded_deleted' => $result['succeeded'],
                'total_deleted' => $result['total'],
            ]);

        } catch (\Exception $e) {
            Log::error('OTP cleanup job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('OTP cleanup job failed permanently', [
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }
}
