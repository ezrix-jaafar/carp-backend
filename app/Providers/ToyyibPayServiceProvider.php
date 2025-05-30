<?php

namespace App\Providers;

use App\Services\ToyyibPayService;
use Illuminate\Support\ServiceProvider;

class ToyyibPayServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(ToyyibPayService::class, function ($app) {
            return new ToyyibPayService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
