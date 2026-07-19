<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
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
        // Keyed by email+IP so one attacker can't lock out a real user by hammering their address,
        // while still capping how fast any single (email, IP) pair can guess passwords.
        RateLimiter::for('login', function ($request) {
            return Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip());
        });

        RateLimiter::for('register', function ($request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // Mirrors the login limiter: keyed by email+IP so one attacker can't lock
        // a real user out of requesting their own reset link, while still capping
        // how fast any single (email, IP) pair can spam the mailer.
        RateLimiter::for('forgot-password', function ($request) {
            return Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip());
        });

        RateLimiter::for('reset-password', function ($request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        // Each message can trigger a real, billed Anthropic API call (and possibly a
        // multi-round tool-use loop) - cap how fast one admin can fire them off.
        RateLimiter::for('visioner-chat', function ($request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        // Public catalog/search/product-detail/reviews reads have no auth to key off,
        // so this is per-IP only. 60/min (1/sec sustained) comfortably covers a real
        // shopper paginating, re-sorting, and typing a live search box, while still
        // capping a scraper/bot from crawling the whole catalog in seconds.
        RateLimiter::for('catalog-read', function ($request) {
            return Limit::perMinute(60)->by($request->ip());
        });
    }
}
