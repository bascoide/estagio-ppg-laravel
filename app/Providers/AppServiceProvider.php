<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        Carbon::setLocale(config('app.locale'));

        RateLimiter::for('login', function (Request $request) {
            $email = strtolower((string) $request->input('email', ''));

            return Limit::perMinute(5)->by($email . '|' . $request->ip());
        });

        RateLimiter::for('registration', function (Request $request) {
            return Limit::perMinute(3)->by($request->ip());
        });

        RateLimiter::for('verification', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('uploads', function (Request $request) {
            $userKey = (string) ($request->session()->get('user_id') ?? $request->ip());

            return Limit::perMinute(5)->by($userKey . '|' . $request->route()?->getName());
        });

        RateLimiter::for('authenticated', function (Request $request) {
            $userKey = (string) ($request->session()->get('user_id') ?? $request->ip());

            return Limit::perMinute(120)->by($userKey);
        });

        RateLimiter::for('admin', function (Request $request) {
            $userKey = (string) ($request->session()->get('user_id') ?? $request->ip());

            return Limit::perMinute(180)->by($userKey);
        });
    }
}
