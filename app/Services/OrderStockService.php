<?php

namespace App\Services;

use App\Http\Controllers\Api\InventoryController;
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
    }
}
