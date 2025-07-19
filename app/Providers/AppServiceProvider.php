<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\DeepLService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(DeepLService::class, function () {
            return new DeepLService();
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
