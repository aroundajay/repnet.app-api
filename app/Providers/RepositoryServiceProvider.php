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
use Illuminate\Support\ServiceProvider;


class RepositoryServiceProvider extends ServiceProvider {
    /**
     * Register any application services.
     */
    public function register(): void
    {
        /**
         * Amenity Repository
         */
        $this->app->singleton(AmenityRepository::class, function () {
            return new AmenityRepository();
        });

        /**
         * File Repository
         */
        $this->app->singleton(FileRepository::class, function () {
            return new FileRepository();
        });

        /**
         * Gym Repository
         */
        $this->app->singleton(GymRepository::class, function () {
            return new GymRepository();
        });

        /**
         * Gym User Repository
         */
        $this->app->singleton(GymUserRepository::class, function () {
            return new GymUserRepository();
        });

        /**
         * Otp Repository
         */
        $this->app->singleton(OtpRepository::class, function () {
            return new OtpRepository();
        });

        /**
         * User Repository
         */
        $this->app->singleton(UserRepository::class, function () {
            return new UserRepository();
        });

        /**
         * Workout Type Repository
         */
        $this->app->singleton(WorkoutTypeRepository::class, function () {
            return new WorkoutTypeRepository();
        });

        /**
         * Notification Repository
         */
        $this->app->singleton(NotificationRepository::class, function () {
            return new NotificationRepository();
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