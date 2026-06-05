<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Scan;
use App\Observers\ScanObserver;

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
        Scan::observe(ScanObserver::class);
    }
}
