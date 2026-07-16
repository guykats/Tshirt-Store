<?php

namespace Tests\Feature\Api;

use App\Mail\OrderConfirmationMail;
use App\Models\Design;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\PayPalClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

class CheckoutTest extends TestCase
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

    public function test_guests_cannot_start_checkout(): void
    {
        $variant = $this->makeVariant();

        $this->postJson('/api/checkout', [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'shipping_address' => $this->validAddress(),
        ])->assertUnauthorized();
    }

    public function test_checkout_requires_a_shipping_address(): void
    {
        $user = User::factory()->create();
        $variant = $this->makeVariant();

        $response = $this->actingAs($user)->postJson('/api/checkout', [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('shipping_address');
    }

    public function test_checkout_rejects_a_quantity_exceeding_stock(): void
    {
        $user = User::factory()->create();
        $variant = $this->makeVariant(['stock_quantity' => 2]);

        $response = $this->actingAs($user)->postJson('/api/checkout', [
            'product_variant_id' => $variant->id,
            'quantity' => 3,
            'shipping_address' => $this->validAddress(),
        ]);

        $response->assertStatus(422)->assertJsonPath('message', 'Not enough stock for the requested quantity.');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_a_successful_checkout_creates_an_order_and_a_paypal_order(): void
    {
        $user = User::factory()->create();
        $variant = $this->makeVariant();

        $this->mock(PayPalClient::class, function ($mock) {
            $mock->shouldReceive('createOrder')->once()->andReturn(['id' => 'PAYPAL-ORDER-123']);
        });

        $response = $this->actingAs($user)->postJson('/api/checkout', [
            'product_variant_id' => $variant->id,
            'quantity' => 2,
            'shipping_address' => $this->validAddress(),
        ]);

        $response->assertCreated()
            ->assertJsonPath('paypal_order_id', 'PAYPAL-ORDER-123')
            ->assertJsonPath('order.total_amount', 60);

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'paypal_order_id' => 'PAYPAL-ORDER-123',
            'payment_status' => 'unpaid',
            'status' => 'pending_approval',
        ]);
    }

    public function test_checkout_still_creates_a_local_order_when_paypal_is_unreachable(): void
    {
        $user = User::factory()->create();
        $variant = $this->makeVariant();

        $this->mock(PayPalClient::class, function ($mock) {
            $mock->shouldReceive('createOrder')->once()->andThrow(new RuntimeException('PayPal down'));
        });

        $response = $this->actingAs($user)->postJson('/api/checkout', [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'shipping_address' => $this->validAddress(),
        ]);

        $response->assertStatus(502);
        $this->assertDatabaseHas('orders', ['user_id' => $user->id, 'paypal_order_id' => null]);
    }

    public function test_only_the_order_owner_can_capture_payment(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $variant = $this->makeVariant();

        $order = $owner->orders()->create([
            'order_number' => 'ORD-TEST-1',
            'subtotal' => 30,
            'total_amount' => 30,
            'paypal_order_id' => 'PAYPAL-1',
            'shipping_address_id' => $owner->addresses()->create(array_merge(['type' => 'shipping'], $this->validAddress()))->id,
            'billing_address_id' => $owner->addresses()->first()->id,
        ]);

        $this->actingAs($stranger)->postJson("/api/checkout/{$order->id}/capture")->assertForbidden();
    }

    public function test_capturing_payment_marks_the_order_paid_and_sends_confirmation_email(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $variant = $this->makeVariant();
        $address = $user->addresses()->create(array_merge(['type' => 'shipping'], $this->validAddress()));

        $order = $user->orders()->create([
            'order_number' => 'ORD-TEST-2',
            'subtotal' => 30,
            'total_amount' => 30,
            'paypal_order_id' => 'PAYPAL-2',
            'shipping_address_id' => $address->id,
            'billing_address_id' => $address->id,
        ]);
        $order->items()->create([
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'unit_price' => 30,
            'subtotal' => 30,
        ]);

        $this->mock(PayPalClient::class, function ($mock) {
            $mock->shouldReceive('captureOrder')->once()->andReturn([
                'status' => 'COMPLETED',
                'purchase_units' => [['payments' => ['captures' => [['id' => 'CAPTURE-123']]]]],
            ]);
        });

        $response = $this->actingAs($user)->postJson("/api/checkout/{$order->id}/capture");

        $response->assertOk()->assertJsonPath('data.payment_status', 'paid');
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'payment_status' => 'paid',
            'paypal_transaction_id' => 'CAPTURE-123',
        ]);
        Mail::assertSent(OrderConfirmationMail::class, fn ($mail) => $mail->order->id === $order->id);
    }

    public function test_a_declined_capture_marks_the_order_failed_without_sending_email(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $address = $user->addresses()->create(array_merge(['type' => 'shipping'], $this->validAddress()));

        $order = $user->orders()->create([
            'order_number' => 'ORD-TEST-3',
            'subtotal' => 30,
            'total_amount' => 30,
            'paypal_order_id' => 'PAYPAL-3',
            'shipping_address_id' => $address->id,
            'billing_address_id' => $address->id,
        ]);

        $this->mock(PayPalClient::class, function ($mock) {
            $mock->shouldReceive('captureOrder')->once()->andReturn(['status' => 'DECLINED']);
        });

        $response = $this->actingAs($user)->postJson("/api/checkout/{$order->id}/capture");

        $response->assertStatus(422);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_status' => 'failed']);
        Mail::assertNothingSent();
    }
}
