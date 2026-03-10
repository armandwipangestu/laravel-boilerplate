<?php

namespace App\Providers;

use App\Repositories\User\Interfaces\UserRepositoryInterface;
use App\Repositories\User\UserRepository;

use App\Repositories\Auth\Interfaces\AuthRepositoryInterface;
use App\Repositories\Auth\AuthRepository;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(AuthRepositoryInterface::class, AuthRepository::class);
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
