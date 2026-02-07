<?php

namespace App\Providers;

use App\Repositories\OtpRepository;
use App\Repositories\UserRepository;
use App\Services\OtpService;
use App\Services\SmsService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register OtpRepository as singleton
        // (single instance for entire request lifecycle)
        $this->app->singleton(OtpRepository::class, function () {
            return new OtpRepository();
        });

        // Register SmsService as singleton
        $this->app->singleton(SmsService::class, function () {
            return new SmsService();
        });

        // Register OtpService as singleton with dependencies
        $this->app->singleton(OtpService::class, function ($app) {
            return new OtpService(
                $app->make(OtpRepository::class),
                $app->make(UserRepository::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
