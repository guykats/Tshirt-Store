<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\SystemEvent;
use App\Services\OrderStockService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('app:expire-abandoned-orders')]
#[Description('Cancel orders whose checkout was started but never paid for within the reservation window, and restore the stock they reserved.')]
class ExpireAbandonedOrders extends Command
{
    public function __construct(private readonly OrderStockService $stock)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $minutes = (int) config('checkout.reservation_minutes', 60);
        $cutoff = now()->subMinutes($minutes);

        // Candidate list only, deliberately not the source of truth for the
        // actual decision: isCancellable() (unpaid + still pre-fulfillment)
        // is re-checked below on a row-locked copy of each order, the same
        // lock-then-recheck pattern OrderController::cancel()/refund() use,
        // so a payment that captures concurrently with this command running
        // can never race its way into having its stock wrongly restored.
        $candidates = Order::query()
            ->where('payment_status', '!=', 'paid')
            ->whereIn('status', ['pending_approval', 'approved'])
            ->where('created_at', '<', $cutoff)
            ->pluck('id');

        $expiredCount = 0;

        foreach ($candidates as $orderId) {
            $expired = DB::transaction(function () use ($orderId) {
                $locked = Order::where('id', $orderId)->lockForUpdate()->first();

                // created_at can't change out from under us, so the only
                // thing worth re-checking under the lock is isCancellable():
                // a concurrent PayPal capture (payment_status -> 'paid') or
                // an admin advancing/cancelling the order between the query
                // above and this transaction acquiring the lock.
                if (! $locked || ! $locked->isCancellable()) {
                    return null;
                }

                $locked->update(['status' => 'cancelled']);
                $this->stock->restore($locked);

                return $locked;
            });

            if (! $expired) {
                continue;
            }

            $expiredCount++;

            SystemEvent::log(
                'order.expired',
                "Order {$expired->order_number} auto-cancelled after sitting unpaid for over {$minutes} minutes; reserved stock restored.",
                'schedule:run',
                'system',
                ['order_id' => $expired->id],
            );
        }

        $this->info("Expired {$expiredCount} abandoned order(s).");

        return self::SUCCESS;
    }
}
