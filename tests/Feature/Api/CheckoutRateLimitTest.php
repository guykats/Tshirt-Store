<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class CheckoutRateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The 'checkout' limiter is keyed by IP; clear it between tests so one
        // test's hits don't bleed into the next (they share the same
        // testing-client IP by default).
        RateLimiter::clear('checkout:127.0.0.1');
    }

    /**
     * Deliberately invalid payload (no fields at all) so every request fails
     * validation with a 422 rather than actually creating a guest/order —
     * throttle middleware runs before form validation, so this still
     * consumes the 'checkout' limiter bucket exactly like a real attempt
     * would, without the side effects of a full checkout.
     */
    protected function hitCheckout()
    {
        return $this->postJson('/api/checkout', []);
    }

    public function test_a_shopper_retrying_a_failed_checkout_a_few_times_is_never_throttled(): void
    {
        // Comfortably under the 10/min cap — e.g. retrying after a declined
        // card or a mistyped coupon a handful of times.
        for ($i = 0; $i < 5; $i++) {
            $response = $this->hitCheckout();
            $response->assertStatus(422);
        }
    }

    public function test_hammering_checkout_past_the_per_ip_limit_returns_429(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $response = $this->hitCheckout();
            $response->assertStatus(422);
        }

        // The 11th request within the same minute, from the same IP, exceeds
        // the 'checkout' limiter and must be rejected before validation even
        // runs.
        $this->hitCheckout()->assertStatus(429);
    }
}
