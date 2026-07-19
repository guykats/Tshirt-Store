<?php

namespace Tests\Feature\Api;

use App\Mail\OrderRefundedMail;
use App\Models\Coupon;
use App\Models\Design;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\PayPalClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CouponCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function makeVariant(array $overrides = []): ProductVariant
    {
        $design = Design::create(['title' => 'Test Design', 'status' => 'approved']);

        $product = Product::create([
            'design_id' => $design->id,
            'name' => 'Test Tee',
            'slug' => 'test-tee-'.uniqid(),
            'base_price' => 30.00,
            'sku' => 'TT-'.uniqid(),
            'status' => 'active',
        ]);

        return $product->variants()->create(array_merge([
            'size' => 'M',
            'color' => 'Black',
            'sku' => 'TT-M-BLK-'.uniqid(),
            'stock_quantity' => 10,
        ], $overrides));
    }

    protected function validAddress(): array
    {
        return [
            'full_name' => 'Test Buyer',
            'line1' => '1 Test St',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
        ];
    }

    public function test_a_valid_percent_coupon_reduces_the_total_amount(): void
    {
        $user = User::factory()->create();
        $variant = $this->makeVariant();
        Coupon::create(['code' => 'SAVE10', 'type' => 'percent', 'value' => 10, 'active' => true]);

        $this->mock(PayPalClient::class, function ($mock) {
            $mock->shouldReceive('createOrder')->once()->andReturn(['id' => 'PAYPAL-COUPON-1']);
        });

        $response = $this->actingAs($user)->postJson('/api/checkout', [
            'code' => 'save10',
            'product_variant_id' => $variant->id,
            'quantity' => 2,
            'shipping_address' => $this->validAddress(),
        ]);

        // subtotal = 60, 10% off = 6, total = 54
        $response->assertCreated()
            ->assertJsonPath('order.subtotal', 60)
            ->assertJsonPath('order.discount_amount', 6)
            ->assertJsonPath('order.discount_code', 'SAVE10')
            ->assertJsonPath('order.total_amount', 54);

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'discount_code' => 'SAVE10',
            'discount_amount' => 6,
            'total_amount' => 54,
        ]);

        $this->assertDatabaseHas('coupons', ['code' => 'SAVE10', 'redemptions_count' => 1]);
    }

    public function test_a_valid_fixed_amount_coupon_reduces_the_total_amount(): void
    {
        $user = User::factory()->create();
        $variant = $this->makeVariant();
        Coupon::create(['code' => 'FLAT5', 'type' => 'fixed', 'value' => 5, 'active' => true]);

        $this->mock(PayPalClient::class, function ($mock) {
            $mock->shouldReceive('createOrder')->once()->andReturn(['id' => 'PAYPAL-COUPON-2']);
        });

        $response = $this->actingAs($user)->postJson('/api/checkout', [
            'code' => 'FLAT5',
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'shipping_address' => $this->validAddress(),
        ]);

        $response->assertCreated()
            ->assertJsonPath('order.discount_amount', 5)
            ->assertJsonPath('order.total_amount', 25);
    }

    public function test_an_unknown_coupon_code_is_rejected_with_a_422(): void
    {
        $user = User::factory()->create();
        $variant = $this->makeVariant();

        $this->mock(PayPalClient::class, function ($mock) {
            $mock->shouldNotReceive('createOrder');
        });

        $response = $this->actingAs($user)->postJson('/api/checkout', [
            'code' => 'DOESNOTEXIST',
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'shipping_address' => $this->validAddress(),
        ]);

        $response->assertStatus(422)->assertJsonPath('message', 'This coupon code is not valid.');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_an_expired_coupon_code_is_rejected_with_a_422(): void
    {
        $user = User::factory()->create();
        $variant = $this->makeVariant();
        Coupon::create([
            'code' => 'EXPIRED', 'type' => 'percent', 'value' => 10, 'active' => true,
            'expires_at' => now()->subDay(),
        ]);

        $this->mock(PayPalClient::class, function ($mock) {
            $mock->shouldNotReceive('createOrder');
        });

        $response = $this->actingAs($user)->postJson('/api/checkout', [
            'code' => 'EXPIRED',
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'shipping_address' => $this->validAddress(),
        ]);

        $response->assertStatus(422)->assertJsonPath('message', 'This coupon code has expired.');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_an_exhausted_coupon_code_is_rejected_with_a_422(): void
    {
        $user = User::factory()->create();
        $variant = $this->makeVariant();
        Coupon::create([
            'code' => 'MAXEDOUT', 'type' => 'fixed', 'value' => 5, 'active' => true,
            'max_redemptions' => 1, 'redemptions_count' => 1,
        ]);

        $this->mock(PayPalClient::class, function ($mock) {
            $mock->shouldNotReceive('createOrder');
        });

        $response = $this->actingAs($user)->postJson('/api/checkout', [
            'code' => 'MAXEDOUT',
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'shipping_address' => $this->validAddress(),
        ]);

        $response->assertStatus(422)->assertJsonPath('message', 'This coupon code has already been fully redeemed.');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_an_inactive_coupon_code_is_rejected_with_a_422(): void
    {
        $user = User::factory()->create();
        $variant = $this->makeVariant();
        Coupon::create(['code' => 'DISABLED', 'type' => 'percent', 'value' => 10, 'active' => false]);

        $response = $this->actingAs($user)->postJson('/api/checkout', [
            'code' => 'DISABLED',
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'shipping_address' => $this->validAddress(),
        ]);

        $response->assertStatus(422)->assertJsonPath('message', 'This coupon code is not valid.');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_checkout_without_a_code_leaves_discount_fields_at_their_defaults(): void
    {
        $user = User::factory()->create();
        $variant = $this->makeVariant();

        $this->mock(PayPalClient::class, function ($mock) {
            $mock->shouldReceive('createOrder')->once()->andReturn(['id' => 'PAYPAL-NO-COUPON']);
        });

        $response = $this->actingAs($user)->postJson('/api/checkout', [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'shipping_address' => $this->validAddress(),
        ]);

        $response->assertCreated()
            ->assertJsonPath('order.discount_code', null)
            ->assertJsonPath('order.discount_amount', 0)
            ->assertJsonPath('order.total_amount', 30);
    }

    public function test_an_orders_discount_amount_survives_cancellation(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $address = $customer->addresses()->create(array_merge(['type' => 'shipping'], $this->validAddress()));

        $order = $customer->orders()->create([
            'order_number' => 'ORD-'.uniqid(),
            'subtotal' => 30,
            'discount_code' => 'SAVE10',
            'discount_amount' => 3,
            'total_amount' => 27,
            'shipping_address_id' => $address->id,
            'billing_address_id' => $address->id,
        ]);

        $response = $this->actingAs($customer)->postJson("/api/orders/{$order->id}/cancel");

        $response->assertOk()->assertJsonPath('data.status', 'cancelled');
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'cancelled',
            'discount_code' => 'SAVE10',
            'discount_amount' => 3,
            'total_amount' => 27,
        ]);
    }

    public function test_an_orders_discount_amount_survives_refund(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $address = $customer->addresses()->create(array_merge(['type' => 'shipping'], $this->validAddress()));

        $order = $customer->orders()->create([
            'order_number' => 'ORD-'.uniqid(),
            'subtotal' => 30,
            'discount_code' => 'FLAT5',
            'discount_amount' => 5,
            'total_amount' => 25,
            'status' => 'approved',
            'payment_status' => 'paid',
            'paypal_transaction_id' => 'CAPTURE-COUPON-1',
            'shipping_address_id' => $address->id,
            'billing_address_id' => $address->id,
        ]);

        $this->mock(PayPalClient::class, function ($mock) {
            $mock->shouldReceive('refundCapture')->once()->with('CAPTURE-COUPON-1')->andReturn(['status' => 'COMPLETED']);
        });

        $response = $this->actingAs($admin)->postJson("/api/orders/{$order->id}/refund");

        $response->assertOk()->assertJsonPath('data.status', 'refunded');
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'refunded',
            'payment_status' => 'refunded',
            'discount_code' => 'FLAT5',
            'discount_amount' => 5,
            'total_amount' => 25,
        ]);

        Mail::assertSent(OrderRefundedMail::class, fn ($mail) => $mail->order->id === $order->id);
    }
}
