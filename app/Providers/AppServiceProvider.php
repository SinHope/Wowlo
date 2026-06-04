<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // In production every URL/asset/redirect must be https — guards against
        // mixed-content and ensures secure cookies behind the Render proxy.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
