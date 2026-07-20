<?php

namespace Tests\Feature\Console;

use App\Http\Controllers\Api\InventoryController;
use App\Models\Design;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CheckoutController::store decrements product_variants.stock_quantity the
 * moment an order is placed, before payment is ever captured. If the buyer
 * never comes back to pay, app:expire-abandoned-orders is what eventually
 * cancels that order and gives the reservation back — see
 * App\Console\Commands\ExpireAbandonedOrders.
 */
class ExpireAbandonedOrdersCommandTest extends TestCase
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
     * Build an order the same shape checkout would have produced, then
     * force its created_at to a specific instant so we can simulate "placed
     * $ageInMinutes ago" without waiting or faking the whole clock.
     */
    protected function makeOrderWithItem(User $owner, ProductVariant $variant, int $quantity, int $ageInMinutes, array $overrides = []): Order
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

        Order::where('id', $order->id)->update(['created_at' => now()->subMinutes($ageInMinutes)]);

        return $order->fresh();
    }

    public function test_an_old_unpaid_order_is_expired_and_its_stock_restored(): void
    {
        config(['checkout.reservation_minutes' => 60]);

        $customer = User::factory()->create(['role' => 'customer']);
        // stock_quantity starts at 5, simulating that this order already
        // reserved (decremented) 3 units at checkout time.
        $variant = $this->makeVariant(['stock_quantity' => 5]);
        $order = $this->makeOrderWithItem($customer, $variant, 3, ageInMinutes: 90);

        $this->artisan('app:expire-abandoned-orders')->assertExitCode(0);

        $order->refresh();
        $this->assertSame('cancelled', $order->status);
        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->id,
            'stock_quantity' => 8,
        ]);
        $this->assertDatabaseHas('system_events', [
            'event_type' => 'order.expired',
        ]);
    }

    public function test_a_recent_unpaid_order_is_left_untouched(): void
    {
        config(['checkout.reservation_minutes' => 60]);

        $customer = User::factory()->create(['role' => 'customer']);
        $variant = $this->makeVariant(['stock_quantity' => 5]);
        $order = $this->makeOrderWithItem($customer, $variant, 3, ageInMinutes: 10);

        $this->artisan('app:expire-abandoned-orders')->assertExitCode(0);

        $order->refresh();
        $this->assertSame('pending_approval', $order->status);
        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->id,
            'stock_quantity' => 5,
        ]);
        $this->assertDatabaseMissing('system_events', [
            'event_type' => 'order.expired',
        ]);
    }

    public function test_an_old_but_already_paid_order_is_left_untouched(): void
    {
        config(['checkout.reservation_minutes' => 60]);

        $customer = User::factory()->create(['role' => 'customer']);
        $variant = $this->makeVariant(['stock_quantity' => 5]);
        $order = $this->makeOrderWithItem($customer, $variant, 3, ageInMinutes: 90, overrides: [
            'status' => 'approved',
            'payment_status' => 'paid',
            'paypal_transaction_id' => 'CAPTURE-EXPIRE-1',
        ]);

        $this->artisan('app:expire-abandoned-orders')->assertExitCode(0);

        $order->refresh();
        $this->assertSame('approved', $order->status);
        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->id,
            'stock_quantity' => 5,
        ]);
        $this->assertDatabaseMissing('system_events', [
            'event_type' => 'order.expired',
        ]);
    }

    public function test_restoring_stock_above_the_low_stock_threshold_re_arms_the_alert(): void
    {
        config(['checkout.reservation_minutes' => 60]);

        $customer = User::factory()->create(['role' => 'customer']);
        $variant = $this->makeVariant([
            'stock_quantity' => InventoryController::DEFAULT_THRESHOLD - 1,
            'low_stock_alerted_at' => now(),
        ]);
        $order = $this->makeOrderWithItem($customer, $variant, 10, ageInMinutes: 90);

        $this->artisan('app:expire-abandoned-orders')->assertExitCode(0);

        $variant->refresh();
        $this->assertSame(InventoryController::DEFAULT_THRESHOLD - 1 + 10, $variant->stock_quantity);
        $this->assertNull($variant->low_stock_alerted_at);
    }

    public function test_it_respects_a_configured_reservation_window(): void
    {
        config(['checkout.reservation_minutes' => 30]);

        $customer = User::factory()->create(['role' => 'customer']);
        $variant = $this->makeVariant(['stock_quantity' => 5]);
        $order = $this->makeOrderWithItem($customer, $variant, 3, ageInMinutes: 45);

        $this->artisan('app:expire-abandoned-orders')->assertExitCode(0);

        $order->refresh();
        $this->assertSame('cancelled', $order->status);
        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->id,
            'stock_quantity' => 8,
        ]);
    }
}
