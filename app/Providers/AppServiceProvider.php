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

        // Guest checkout (POST /checkout) is reachable with no session, same as
        // register/login - keyed per-IP (no email field is guaranteed present/valid
        // before validation runs) at the same order of magnitude as 'register' so a
        // real shopper retrying a declined card or a mistyped coupon a few times
        // never gets blocked, while a script can't hammer coupon codes, spin up
        // unlimited guest User rows, or spam PayPal order-creation calls.
        RateLimiter::for('checkout', function ($request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        // Guest order lookup (POST /orders/lookup) is the only way a guest can
        // find their order again once their post-checkout session/cookie is
        // gone, since guest accounts have no real password to log back in
        // with — but it's also a two-field guessing surface (order_number +
        // email), so keep it tighter than 'checkout' and per-IP only (no
        // email field is guaranteed valid before validation runs, same
        // reasoning as 'checkout').
        RateLimiter::for('order-lookup', function ($request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // Each message can trigger a real, billed Anthropic API call (and possibly a
        // multi-round tool-use loop) - cap how fast one admin can fire them off.
        RateLimiter::for('visioner-chat', function ($request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        // Review submission/deletion (POST/DELETE /products/{product}/reviews) is
        // behind auth:sanctum so a real user is always available to key off, same
        // as 'visioner-chat' - keyed by user id so one abusive account can't be
        // used to hammer the endpoint (e.g. repeatedly retrying after the
        // duplicate-review 422) while other users are unaffected.
        RateLimiter::for('reviews', function ($request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        // Public catalog/search/product-detail/reviews reads have no auth to key off,
        // so this is per-IP only. 60/min (1/sec sustained) comfortably covers a real
        // shopper paginating, re-sorting, and typing a live search box, while still
        // capping a scraper/bot from crawling the whole catalog in seconds.
        RateLimiter::for('catalog-read', function ($request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        // Generous per-IP cap for the unauthenticated health-check endpoint —
        // uptime monitors typically poll every 1-5 minutes, but this keeps
        // the (cheap, single-query) endpoint from being hammered as a free DoS
        // vector while still comfortably covering multiple monitors/load
        // balancer health probes sharing an IP.
        RateLimiter::for('health-check', function ($request) {
            return Limit::perMinute(120)->by($request->ip());
        });
    }
}
