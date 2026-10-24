<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Vite;
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
        date_default_timezone_set(config('app.timezone', 'Asia/Jakarta'));
        Carbon::setLocale('id');
        date_default_timezone_set(config('app.timezone', 'Asia/Jakarta'));
        Carbon::setLocale('id');
        Schema::defaultStringLength(191);
        Vite::prefetch(concurrency: 3);
    }
}
