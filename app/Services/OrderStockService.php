<?php

namespace App\Services;

use App\Http\Controllers\Api\InventoryController;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\ProductVariant;

/**
 * Gives back the stock CheckoutController::store reserved (decremented) for
 * an order at the moment it was placed — regardless of whether the order was
 * ever paid. Shared by every code path that moves an order into a terminal,
 * non-fulfilled state: OrderController::cancel()/refund() (a human acting on
 * one order) and ExpireAbandonedOrders (the scheduled job acting on orders
 * nobody ever came back to pay for).
 *
 * Also releases the coupon redemption (if any) that CheckoutController::store
 * consumed for the same order at the same moment
 * (`$coupon->increment('redemptions_count')`) — for the same "reserved before
 * payment" reason stock needs restoring: an order that's cancelled, refunded,
 * or auto-expired never benefited from whatever redemption it used, so that
 * redemption shouldn't count against the coupon's max_redemptions.
 *
 * Callers MUST run restore() from inside the same transaction as, and after,
 * a status transition guarded by a row lock on the order (see
 * OrderController::cancel()/refund() and ExpireAbandonedOrders::handle() for
 * the lock-then-recheck pattern) so it can never run twice for the same
 * order.
 */
class OrderStockService
{
    public function restore(Order $order): void
    {
        $order->loadMissing('items');

        foreach ($order->items as $item) {
            $variant = ProductVariant::where('id', $item->product_variant_id)->lockForUpdate()->first();

            if (! $variant) {
                // order_items.product_variant_id is a RESTRICT foreign key
                // (see ProductVariantController::destroy), so this shouldn't
                // be reachable — guarded defensively rather than assumed.
                continue;
            }

            $variant->increment('stock_quantity', $item->quantity);

            // Mirror Admin\ProductVariantController::update: stock coming
            // back above the low-stock threshold re-arms the alert, so the
            // next time this variant sells back down to it, an admin is
            // notified again instead of staying silenced from the order that
            // originally triggered the alert.
            if ($variant->stock_quantity > InventoryController::DEFAULT_THRESHOLD && $variant->low_stock_alerted_at !== null) {
                $variant->forceFill(['low_stock_alerted_at' => null])->save();
            }
        }

        $this->releaseCoupon($order);
    }

    /**
     * Give back the redemption CheckoutController::store consumed for this
     * order's discount_code, if it used one. Row-locked the same way
     * CouponService::lockAndValidate() locks it at checkout, so a concurrent
     * checkout redeeming the same coupon can't race with this decrement.
     * Floors at 0 rather than going negative, since an order can't be
     * restored more than once (see the class-level lock-then-recheck
     * requirement) but this guards defensively against any other drift.
     */
    protected function releaseCoupon(Order $order): void
    {
        if (! $order->discount_code) {
            return;
        }

        $coupon = Coupon::where('code', $order->discount_code)->lockForUpdate()->first();

        if (! $coupon) {
            return;
        }

        $coupon->update(['redemptions_count' => max(0, $coupon->redemptions_count - 1)]);
    }
}
