<?php

namespace Tests\Feature\Api;

use App\Http\Controllers\Api\InventoryController;
use App\Models\Design;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\PayPalClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Checkout decrements product_variants.stock_quantity the moment an order is
 * placed (see CheckoutController::store), whether or not it's ever paid. If
 * the order is later cancelled or refunded, that reservation needs to come
 * back — see OrderController::restoreStock.
 */
class OrderStockRestorationTest extends TestCase
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

    /**
     * Build an order the same shape checkout would have produced: it owns a
     * shipping/billing address and an order_items row against $variant that
     * already "reserved" $quantity units (i.e. the caller is expected to
     * have already decremented stock_quantity to simulate checkout, exactly
     * like CheckoutController::store does).
     */
    protected function makeOrderWithItem(User $owner, ProductVariant $variant, int $quantity, array $overrides = [])
    {
        $address = $owner->addresses()->create([
            'type' => 'shipping',
            'full_name' => 'Test Buyer',
            'line1' => '1 Test St',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
        ]);

        $order = $owner->orders()->create(array_merge([
            'order_number' => 'ORD-'.uniqid(),
            'subtotal' => 30 * $quantity,
            'total_amount' => 30 * $quantity,
            'shipping_address_id' => $address->id,
            'billing_address_id' => $address->id,
        ], $overrides));

        $order->items()->create([
            'product_variant_id' => $variant->id,
            'quantity' => $quantity,
            'unit_price' => 30,
            'subtotal' => 30 * $quantity,
        ]);

        return $order;
    }

    public function test_cancelling_an_order_restores_the_reserved_stock(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        // stock_quantity starts at 5, simulating that this order already
        // reserved (decremented) 3 units at checkout time.
        $variant = $this->makeVariant(['stock_quantity' => 5]);

        $order = $this->makeOrderWithItem($customer, $variant, 3);

        $response = $this->actingAs($customer)->postJson("/api/orders/{$order->id}/cancel");

        $response->assertOk()->assertJsonPath('data.status', 'cancelled');
        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->id,
            'stock_quantity' => 8,
        ]);
    }

    public function test_refunding_an_order_restores_the_reserved_stock(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $variant = $this->makeVariant(['stock_quantity' => 5]);

        $order = $this->makeOrderWithItem($customer, $variant, 4, [
            'status' => 'approved',
            'payment_status' => 'paid',
            'paypal_transaction_id' => 'CAPTURE-RESTORE-1',
        ]);

        $this->mock(PayPalClient::class, function ($mock) {
            $mock->shouldReceive('refundCapture')->once()->with('CAPTURE-RESTORE-1')->andReturn(['status' => 'COMPLETED']);
        });

        $response = $this->actingAs($admin)->postJson("/api/orders/{$order->id}/refund");

        $response->assertOk()->assertJsonPath('data.status', 'refunded');
        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->id,
            'stock_quantity' => 9,
        ]);
    }

    public function test_cancelling_an_order_twice_does_not_restore_stock_twice(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $variant = $this->makeVariant(['stock_quantity' => 5]);

        $order = $this->makeOrderWithItem($customer, $variant, 3);

        $this->actingAs($customer)->postJson("/api/orders/{$order->id}/cancel")->assertOk();
        // The order is no longer cancellable once it's already cancelled, so
        // the second call is rejected before restoreStock() ever runs again.
        $this->actingAs($customer)->postJson("/api/orders/{$order->id}/cancel")->assertStatus(422);

        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->id,
            'stock_quantity' => 8,
        ]);
    }

    public function test_refunding_an_order_twice_does_not_restore_stock_twice(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $variant = $this->makeVariant(['stock_quantity' => 5]);

        $order = $this->makeOrderWithItem($customer, $variant, 4, [
            'status' => 'approved',
            'payment_status' => 'paid',
            'paypal_transaction_id' => 'CAPTURE-RESTORE-2',
        ]);

        $this->mock(PayPalClient::class, function ($mock) {
            $mock->shouldReceive('refundCapture')->once()->with('CAPTURE-RESTORE-2')->andReturn(['status' => 'COMPLETED']);
        });

        $this->actingAs($admin)->postJson("/api/orders/{$order->id}/refund")->assertOk();
        // payment_status is now 'refunded', not 'paid', so the second call is
        // rejected before it ever calls PayPal or restoreStock() again.
        $this->actingAs($admin)->postJson("/api/orders/{$order->id}/refund")->assertStatus(422);

        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->id,
            'stock_quantity' => 9,
        ]);
    }

    public function test_a_cancelled_order_cannot_then_be_refunded_to_double_restore_stock(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $variant = $this->makeVariant(['stock_quantity' => 5]);

        $order = $this->makeOrderWithItem($customer, $variant, 3);

        $this->actingAs($customer)->postJson("/api/orders/{$order->id}/cancel")->assertOk();
        $this->assertDatabaseHas('product_variants', ['id' => $variant->id, 'stock_quantity' => 8]);

        // Cancelled orders are never marked payment_status = 'paid', so the
        // refund endpoint's own guard rejects this before touching stock.
        $this->actingAs($admin)->postJson("/api/orders/{$order->id}/refund")->assertStatus(422);

        $this->assertDatabaseHas('product_variants', ['id' => $variant->id, 'stock_quantity' => 8]);
    }

    public function test_restoring_stock_above_the_low_stock_threshold_re_arms_the_alert(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $variant = $this->makeVariant([
            'stock_quantity' => InventoryController::DEFAULT_THRESHOLD - 1,
            'low_stock_alerted_at' => now(),
        ]);

        $order = $this->makeOrderWithItem($customer, $variant, 10);

        $this->actingAs($customer)->postJson("/api/orders/{$order->id}/cancel")->assertOk();

        $variant->refresh();
        $this->assertSame(InventoryController::DEFAULT_THRESHOLD - 1 + 10, $variant->stock_quantity);
        $this->assertNull($variant->low_stock_alerted_at);
    }
}
