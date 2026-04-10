<?php

namespace App\Providers;

use App\Repositories\AmenityRepository;
use App\Repositories\FileRepository;
use App\Repositories\GymRepository;
use App\Repositories\GymUserRepository;
use App\Repositories\NotificationRepository;
use App\Repositories\OtpRepository;
use App\Repositories\UserRepository;
use App\Repositories\WorkoutTypeRepository;
use App\Services\AmenityService;
use App\Services\FileService;
use App\Services\GymService;
use App\Services\GymShiftService;
use App\Services\GymShiftPlanService;
use App\Repositories\GymShiftRepository;
use App\Repositories\GymShiftPlanRepository;
use App\Services\NotificationService;
use App\Services\OtpService;
use App\Services\SmsService;
use App\Services\UserService;
use App\Services\WorkoutTypeService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        /**
         * Register File service
         */
        $this->app->singleton(FileService::class, function ($app) {
            return new FileService(
                $app->make(FileRepository::class)
            );
        });

        /**
         * Register Amenity service
         */
        $this->app->singleton(AmenityService::class, function ($app) {
            return new AmenityService(
                $app->make(AmenityRepository::class)
            );
        });

        /**
         * Register Gym service
         */
        $this->app->singleton(GymService::class, function ($app) {
            return new GymService(
                $app->make(GymRepository::class),
                $app->make(GymUserRepository::class)
            );
        });

        /**
         * Register Gym Shift service
         */
        $this->app->singleton(GymShiftService::class, function ($app) {
            return new GymShiftService(
                $app->make(GymShiftRepository::class)
            );
        });

        /**
         * Register Gym Shift Plan service
         */
        $this->app->singleton(GymShiftPlanService::class, function ($app) {
            return new GymShiftPlanService(
                $app->make(GymShiftPlanRepository::class)
            );
        });

        // Register OtpService as singleton with dependencies
        $this->app->singleton(OtpService::class, function ($app) {
            return new OtpService(
                $app->make(OtpRepository::class),
                $app->make(UserRepository::class)
            );
        });

        // Register SmsService as singleton
        $this->app->singleton(SmsService::class, function () {
            return new SmsService();
        });

        // Register UserService as singleton with dependencies
        $this->app->singleton(UserService::class, function ($app) {
            return new UserService(
                $app->make(UserRepository::class)
            );
        });

        // Register WorkoutTypeService as singleton with dependencies
        $this->app->singleton(WorkoutTypeService::class, function ($app) {
            return new WorkoutTypeService(
                $app->make(WorkoutTypeRepository::class)
            );
        });

        /**
         * Register Notification service
         */
        $this->app->singleton(NotificationService::class, function ($app) {
            return new NotificationService(
                $app->make(NotificationRepository::class)
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
