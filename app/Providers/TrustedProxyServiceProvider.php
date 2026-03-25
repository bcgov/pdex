<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class TrustedProxyServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Force the application to use APP_URL for all generated URLs
        // This is critical when behind CloudFront/ALB where the Host header
        // may not match the public domain
        if (config('app.url')) {
            URL::forceRootUrl(config('app.url'));
        }

        // Force HTTPS if APP_URL starts with https
        if (str_starts_with(config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }
    }
}
