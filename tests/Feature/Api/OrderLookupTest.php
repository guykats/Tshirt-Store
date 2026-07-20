<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class OrderLookupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The 'order-lookup' limiter is keyed by IP; clear it between tests so
        // one test's hits don't bleed into the next (they share the same
        // testing-client IP by default).
        RateLimiter::clear('order-lookup:127.0.0.1');
    }

    protected function makeGuestOrder(array $overrides = [])
    {
        $guest = User::factory()->create([
            'email' => 'guest-buyer@example.com',
            'is_guest' => true,
        ]);

        $address = $guest->addresses()->create([
            'type' => 'shipping',
            'full_name' => 'Guest Buyer',
            'line1' => '1 Test St',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
        ]);

        return $guest->orders()->create(array_merge([
            'order_number' => 'ORD-'.uniqid(),
            'subtotal' => 30,
            'total_amount' => 30,
            'shipping_address_id' => $address->id,
            'billing_address_id' => $address->id,
        ], $overrides));
    }

    public function test_correct_order_number_and_email_returns_the_order(): void
    {
        $order = $this->makeGuestOrder();

        $response = $this->postJson('/api/orders/lookup', [
            'order_number' => $order->order_number,
            'email' => 'guest-buyer@example.com',
        ]);

        $response->assertOk()->assertJsonPath('data.order_number', $order->order_number);
    }

    public function test_correct_order_number_and_email_is_case_insensitive_on_email(): void
    {
        $order = $this->makeGuestOrder();

        $response = $this->postJson('/api/orders/lookup', [
            'order_number' => $order->order_number,
            'email' => 'Guest-Buyer@Example.com',
        ]);

        $response->assertOk()->assertJsonPath('data.order_number', $order->order_number);
    }

    public function test_wrong_email_for_a_real_order_number_is_rejected(): void
    {
        $order = $this->makeGuestOrder();

        $response = $this->postJson('/api/orders/lookup', [
            'order_number' => $order->order_number,
            'email' => 'someone-else@example.com',
        ]);

        $response->assertStatus(404);
    }

    public function test_an_order_number_that_does_not_exist_is_rejected_identically(): void
    {
        $response = $this->postJson('/api/orders/lookup', [
            'order_number' => 'ORD-DOES-NOT-EXIST',
            'email' => 'guest-buyer@example.com',
        ]);

        $response->assertStatus(404);
    }

    public function test_missing_fields_fail_validation(): void
    {
        $this->postJson('/api/orders/lookup', [])->assertStatus(422);
    }

    public function test_the_endpoint_is_rate_limited(): void
    {
        $order = $this->makeGuestOrder();

        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/orders/lookup', [
                'order_number' => $order->order_number,
                'email' => 'wrong@example.com',
            ]);
            $response->assertStatus(404);
        }

        // The 6th request within the same minute, from the same IP, exceeds
        // the 'order-lookup' limiter and must be rejected regardless of
        // whether the fields would otherwise have matched.
        $this->postJson('/api/orders/lookup', [
            'order_number' => $order->order_number,
            'email' => 'guest-buyer@example.com',
        ])->assertStatus(429);
    }
}
