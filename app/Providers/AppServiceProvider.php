<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

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
        // Di production (Vercel) paksa semua URL generator pakai https
        // agar asset(), url(), route() tidak menghasilkan http:// (mixed content).
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
