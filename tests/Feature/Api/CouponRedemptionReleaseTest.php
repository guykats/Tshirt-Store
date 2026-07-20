<?php

namespace Tests\Feature\Api;

use App\Models\Coupon;
use App\Models\Design;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\PayPalClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * CheckoutController::store increments a coupon's redemptions_count the
 * moment an order is placed, before payment is ever captured — the same
 * "reserve before payment" pattern already known to need reversing for
 * stock (see OrderStockRestorationTest). OrderStockService::restore() is
 * the shared helper OrderController::cancel()/refund() and
 * ExpireAbandonedOrders all call, and it must also give back the coupon
 * redemption an order consumed, not just the stock.
 */
class CouponRedemptionReleaseTest extends TestCase
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
     * already "reserved" $quantity units, exactly like CheckoutController::store
     * does — optionally with a discount_code that mirrors a coupon redemption
     * having already been counted against it.
     */
    protected function makeOrderWithItem(User $owner, ProductVariant $variant, int $quantity, array $overrides = []): Order
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

    public function test_cancelling_an_order_that_used_a_coupon_restores_its_redemptions_count(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $variant = $this->makeVariant(['stock_quantity' => 5]);
        $coupon = Coupon::create([
            'code' => 'SAVE10', 'type' => 'percent', 'value' => 10, 'active' => true,
            'max_redemptions' => 50, 'redemptions_count' => 12,
        ]);

        $order = $this->makeOrderWithItem($customer, $variant, 3, [
            'discount_code' => $coupon->code,
            'discount_amount' => 9,
        ]);

        $response = $this->actingAs($customer)->postJson("/api/orders/{$order->id}/cancel");

        $response->assertOk()->assertJsonPath('data.status', 'cancelled');
        $this->assertDatabaseHas('coupons', [
            'id' => $coupon->id,
            'redemptions_count' => 11,
        ]);
    }

    public function test_refunding_an_order_that_used_a_coupon_restores_its_redemptions_count(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $variant = $this->makeVariant(['stock_quantity' => 5]);
        $coupon = Coupon::create([
            'code' => 'FLAT5', 'type' => 'fixed', 'value' => 5, 'active' => true,
            'max_redemptions' => 10, 'redemptions_count' => 4,
        ]);

        $order = $this->makeOrderWithItem($customer, $variant, 4, [
            'status' => 'approved',
            'payment_status' => 'paid',
            'paypal_transaction_id' => 'CAPTURE-COUPON-RELEASE-1',
            'discount_code' => $coupon->code,
            'discount_amount' => 5,
        ]);

        $this->mock(PayPalClient::class, function ($mock) {
            $mock->shouldReceive('refundCapture')->once()->with('CAPTURE-COUPON-RELEASE-1')->andReturn(['status' => 'COMPLETED']);
        });

        $response = $this->actingAs($admin)->postJson("/api/orders/{$order->id}/refund");

        $response->assertOk()->assertJsonPath('data.status', 'refunded');
        $this->assertDatabaseHas('coupons', [
            'id' => $coupon->id,
            'redemptions_count' => 3,
        ]);
    }

    public function test_auto_expiring_an_abandoned_order_that_used_a_coupon_restores_its_redemptions_count(): void
    {
        config(['checkout.reservation_minutes' => 60]);

        $customer = User::factory()->create(['role' => 'customer']);
        $variant = $this->makeVariant(['stock_quantity' => 5]);
        $coupon = Coupon::create([
            'code' => 'ABANDONED10', 'type' => 'percent', 'value' => 10, 'active' => true,
            'max_redemptions' => 50, 'redemptions_count' => 20,
        ]);

        $order = $this->makeOrderWithItem($customer, $variant, 3, [
            'discount_code' => $coupon->code,
            'discount_amount' => 9,
        ]);
        Order::where('id', $order->id)->update(['created_at' => now()->subMinutes(90)]);

        $this->artisan('app:expire-abandoned-orders')->assertExitCode(0);

        $order->refresh();
        $this->assertSame('cancelled', $order->status);
        $this->assertDatabaseHas('coupons', [
            'id' => $coupon->id,
            'redemptions_count' => 19,
        ]);
    }

    public function test_cancelling_an_order_placed_without_a_coupon_leaves_other_coupons_untouched(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $variant = $this->makeVariant(['stock_quantity' => 5]);
        $unrelatedCoupon = Coupon::create([
            'code' => 'UNRELATED', 'type' => 'fixed', 'value' => 5, 'active' => true,
            'max_redemptions' => 10, 'redemptions_count' => 6,
        ]);

        $order = $this->makeOrderWithItem($customer, $variant, 3);

        $response = $this->actingAs($customer)->postJson("/api/orders/{$order->id}/cancel");

        $response->assertOk()->assertJsonPath('data.status', 'cancelled');
        $this->assertDatabaseHas('coupons', [
            'id' => $unrelatedCoupon->id,
            'redemptions_count' => 6,
        ]);
    }

    public function test_releasing_a_coupon_already_at_zero_redemptions_never_goes_negative(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $variant = $this->makeVariant(['stock_quantity' => 5]);
        $coupon = Coupon::create([
            'code' => 'ALREADYZERO', 'type' => 'fixed', 'value' => 5, 'active' => true,
            'max_redemptions' => 10, 'redemptions_count' => 0,
        ]);

        $order = $this->makeOrderWithItem($customer, $variant, 3, [
            'discount_code' => $coupon->code,
            'discount_amount' => 5,
        ]);

        $response = $this->actingAs($customer)->postJson("/api/orders/{$order->id}/cancel");

        $response->assertOk()->assertJsonPath('data.status', 'cancelled');
        $this->assertDatabaseHas('coupons', [
            'id' => $coupon->id,
            'redemptions_count' => 0,
        ]);
    }
}
