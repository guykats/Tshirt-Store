<?php

namespace Tests\Feature\Api;

use App\Http\Controllers\Api\InventoryController;
use App\Mail\OrderConfirmationMail;
use App\Models\Design;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Notifications\LowStockAlert;
use App\Services\PayPalClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
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

    public function test_a_guest_can_check_out_without_an_account(): void
    {
        $variant = $this->makeVariant();

        $this->mock(PayPalClient::class, function ($mock) {
            $mock->shouldReceive('createOrder')->once()->andReturn(['id' => 'PAYPAL-GUEST-1']);
        });

        $response = $this->postJson('/api/checkout', [
            'email' => 'guest@example.com',
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'shipping_address' => $this->validAddress(),
        ]);

        $response->assertCreated()->assertJsonPath('paypal_order_id', 'PAYPAL-GUEST-1');

        $guest = User::where('email', 'guest@example.com')->firstOrFail();
        $this->assertTrue($guest->isGuest());
        $this->assertDatabaseHas('orders', [
            'user_id' => $guest->id,
            'paypal_order_id' => 'PAYPAL-GUEST-1',
        ]);

        // The controller logs the newly created guest in for the rest of the
        // browser session (see CheckoutController::store), so the capture
        // step immediately afterwards doesn't need a separate login.
        $this->assertAuthenticatedAs($guest);
    }

    public function test_guest_checkout_requires_an_email(): void
    {
        $variant = $this->makeVariant();

        $response = $this->postJson('/api/checkout', [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'shipping_address' => $this->validAddress(),
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_guest_checkout_is_rejected_when_the_email_already_has_an_account(): void
    {
        $existing = User::factory()->create(['email' => 'already-registered@example.com']);
        $variant = $this->makeVariant();

        $response = $this->postJson('/api/checkout', [
            'email' => 'already-registered@example.com',
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'shipping_address' => $this->validAddress(),
        ]);

        $response->assertStatus(409);
        $this->assertDatabaseCount('orders', 0);
        $this->assertGuest();
        $this->assertDatabaseHas('users', ['id' => $existing->id]);
    }

    public function test_a_guest_cannot_capture_or_view_another_guests_order(): void
    {
        $ownerGuest = User::factory()->create(['is_guest' => true]);
        $otherGuest = User::factory()->create(['is_guest' => true]);
        $variant = $this->makeVariant();

        $address = $ownerGuest->addresses()->create(array_merge(['type' => 'shipping'], $this->validAddress()));

        $order = $ownerGuest->orders()->create([
            'order_number' => 'ORD-TEST-GUEST-1',
            'subtotal' => 30,
            'total_amount' => 30,
            'paypal_order_id' => 'PAYPAL-GUEST-OWNER',
            'shipping_address_id' => $address->id,
            'billing_address_id' => $address->id,
        ]);
        $order->items()->create([
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'unit_price' => 30,
            'subtotal' => 30,
        ]);

        $this->actingAs($otherGuest)->postJson("/api/checkout/{$order->id}/capture")->assertForbidden();
        $this->actingAs($otherGuest)->getJson("/api/orders/{$order->id}")->assertForbidden();
        $this->actingAs($otherGuest)->getJson("/api/orders/{$order->id}/invoice")->assertForbidden();
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

    public function test_a_successful_checkout_decrements_variant_stock(): void
    {
        $user = User::factory()->create();
        $variant = $this->makeVariant(['stock_quantity' => 5]);

        $this->mock(PayPalClient::class, function ($mock) {
            $mock->shouldReceive('createOrder')->once()->andReturn(['id' => 'PAYPAL-ORDER-123']);
        });

        $this->actingAs($user)->postJson('/api/checkout', [
            'product_variant_id' => $variant->id,
            'quantity' => 2,
            'shipping_address' => $this->validAddress(),
        ])->assertCreated();

        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->id,
            'stock_quantity' => 3,
        ]);
    }

    public function test_a_low_stock_alert_is_sent_once_when_a_checkout_takes_a_variant_to_the_threshold(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $buyer = User::factory()->create();
        $variant = $this->makeVariant(['stock_quantity' => InventoryController::DEFAULT_THRESHOLD + 1]);

        $this->mock(PayPalClient::class, function ($mock) {
            $mock->shouldReceive('createOrder')->twice()->andReturn(['id' => 'PAYPAL-LOW-STOCK']);
        });

        // Takes stock from (threshold + 1) down to exactly the threshold — should alert.
        $this->actingAs($buyer)->postJson('/api/checkout', [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'shipping_address' => $this->validAddress(),
        ])->assertCreated();

        Notification::assertSentTo($admin, LowStockAlert::class, fn ($notification) => $notification->variant->id === $variant->id);
        Notification::assertSentTimes(LowStockAlert::class, 1);

        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->id,
            'stock_quantity' => InventoryController::DEFAULT_THRESHOLD,
        ]);

        // A second order against the same, already-alerted variant decrements
        // it further (now below the threshold) but must not re-alert.
        $this->actingAs($buyer)->postJson('/api/checkout', [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'shipping_address' => $this->validAddress(),
        ])->assertCreated();

        Notification::assertSentTimes(LowStockAlert::class, 1);
    }

    public function test_no_low_stock_alert_is_sent_when_a_variant_stays_above_the_threshold(): void
    {
        Notification::fake();

        User::factory()->create(['role' => 'admin']);
        $buyer = User::factory()->create();
        $variant = $this->makeVariant(['stock_quantity' => InventoryController::DEFAULT_THRESHOLD + 10]);

        $this->mock(PayPalClient::class, function ($mock) {
            $mock->shouldReceive('createOrder')->once()->andReturn(['id' => 'PAYPAL-PLENTY-STOCK']);
        });

        $this->actingAs($buyer)->postJson('/api/checkout', [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'shipping_address' => $this->validAddress(),
        ])->assertCreated();

        Notification::assertNothingSent();
    }

    public function test_checkout_rejects_a_variant_of_a_non_active_product(): void
    {
        $user = User::factory()->create();
        $variant = $this->makeVariant();
        $variant->product->update(['status' => 'draft']);

        $response = $this->actingAs($user)->postJson('/api/checkout', [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'shipping_address' => $this->validAddress(),
        ]);

        $response->assertStatus(422)->assertJsonPath('message', 'This product is not currently available.');
        $this->assertDatabaseCount('orders', 0);
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

    public function test_a_guest_receives_the_same_order_confirmation_email_on_capture(): void
    {
        Mail::fake();

        $guest = User::factory()->create(['email' => 'guest-buyer@example.com', 'is_guest' => true]);
        $variant = $this->makeVariant();
        $address = $guest->addresses()->create(array_merge(['type' => 'shipping'], $this->validAddress()));

        $order = $guest->orders()->create([
            'order_number' => 'ORD-TEST-GUEST-MAIL',
            'subtotal' => 30,
            'total_amount' => 30,
            'paypal_order_id' => 'PAYPAL-GUEST-MAIL',
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
                'purchase_units' => [['payments' => ['captures' => [['id' => 'CAPTURE-GUEST-MAIL']]]]],
            ]);
        });

        $response = $this->actingAs($guest)->postJson("/api/checkout/{$order->id}/capture");

        $response->assertOk()->assertJsonPath('data.payment_status', 'paid');
        Mail::assertSent(OrderConfirmationMail::class, fn ($mail) => $mail->order->id === $order->id
            && $mail->order->user->email === 'guest-buyer@example.com');
    }

    public function test_capture_still_succeeds_when_the_confirmation_email_fails_to_send(): void
    {
        Mail::shouldReceive('to')->andReturnSelf();
        Mail::shouldReceive('locale')->andReturnSelf();
        Mail::shouldReceive('send')->andThrow(new \RuntimeException('SMTP unreachable'));

        $user = User::factory()->create();
        $variant = $this->makeVariant();
        $address = $user->addresses()->create(array_merge(['type' => 'shipping'], $this->validAddress()));

        $order = $user->orders()->create([
            'order_number' => 'ORD-TEST-MAILFAIL',
            'subtotal' => 30,
            'total_amount' => 30,
            'paypal_order_id' => 'PAYPAL-MAILFAIL',
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
                'purchase_units' => [['payments' => ['captures' => [['id' => 'CAPTURE-MAILFAIL']]]]],
            ]);
        });

        $response = $this->actingAs($user)->postJson("/api/checkout/{$order->id}/capture");

        $response->assertOk()->assertJsonPath('data.payment_status', 'paid');
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'payment_status' => 'paid',
            'paypal_transaction_id' => 'CAPTURE-MAILFAIL',
        ]);
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
