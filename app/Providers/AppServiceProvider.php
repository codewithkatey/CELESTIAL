<?php

namespace App\Providers;

use App\Services\ImageLibrary;
use App\Services\JsonStorage;
use App\Services\UserService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(JsonStorage::class);
        $this->app->singleton(UserService::class);
        $this->app->singleton(ImageLibrary::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            \URL::forceScheme('https');
        }
    }
}
