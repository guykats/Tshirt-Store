<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Mail\OrderDeliveredMail;
use App\Mail\OrderRefundedMail;
use App\Mail\OrderShippedMail;
use App\Models\Order;
use App\Models\SystemEvent;
use App\Services\InvoiceService;
use App\Services\PayPalClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Order::class);

        $query = Order::query()->with(['user', 'items.productVariant.product']);

        if (! $request->user()->isAdmin()) {
            $query->where('user_id', $request->user()->id);
        } elseif ($request->query('status')) {
            $query->where('status', $request->query('status'));
        }

        return OrderResource::collection($query->latest()->paginate(20));
    }

    public function show(Request $request, Order $order)
    {
        $this->authorize('view', $order);

        return new OrderResource($order->load(['user', 'shippingAddress', 'billingAddress', 'items.productVariant.product']));
    }

    public function approve(Request $request, Order $order)
    {
        $this->authorize('update', $order);

        if ($order->status === 'approved') {
            return new OrderResource($order->load(['user', 'items.productVariant.product']));
        }

        $order->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        SystemEvent::log(
            'order.approved',
            "Order {$order->order_number} approved by {$request->user()->name}.",
            $request->user()->name,
            'user',
            ['order_id' => $order->id],
        );

        return new OrderResource($order->fresh(['user', 'items.productVariant.product']));
    }

    /**
     * Move an order one step forward through the fulfillment sequence
     * (pending_approval → approved → processing → shipped → delivered).
     * Admin-only, and structurally forward-only: it always moves to the
     * single next status in Order::STATUS_SEQUENCE, never skips ahead and
     * never reverses. A caller may optionally pass the `status` it expects
     * to land on (e.g. from a UI showing "Mark as Shipped") and the request
     * is rejected if that doesn't match the actual next status.
     */
    public function advanceStatus(Request $request, Order $order)
    {
        $this->authorize('update', $order);

        $request->validate([
            'status' => ['sometimes', 'string', 'in:'.implode(',', Order::STATUS_SEQUENCE)],
        ]);

        $nextStatus = $order->nextFulfillmentStatus();

        if ($nextStatus === null) {
            return response()->json(['message' => 'This order cannot be advanced further.'], 422);
        }

        if ($request->filled('status') && $request->input('status') !== $nextStatus) {
            return response()->json(['message' => 'Orders can only be advanced one step at a time, in order.'], 422);
        }

        if ($nextStatus === 'shipped') {
            $request->validate([
                'tracking_number' => ['required', 'string', 'max:100'],
                'carrier' => ['required', 'string', 'max:100'],
            ]);
        }

        $attributes = ['status' => $nextStatus];

        if ($nextStatus === 'approved') {
            $attributes['approved_by'] = $request->user()->id;
            $attributes['approved_at'] = now();
        }

        if ($nextStatus === 'shipped') {
            $attributes['tracking_number'] = $request->input('tracking_number');
            $attributes['carrier'] = $request->input('carrier');
        }

        $order->update($attributes);

        SystemEvent::log(
            'order.status_advanced',
            "Order {$order->order_number} advanced to {$nextStatus} by {$request->user()->name}.",
            $request->user()->name,
            'user',
            ['order_id' => $order->id, 'status' => $nextStatus],
        );

        $order->loadMissing('user');

        if ($nextStatus === 'shipped') {
            Mail::to($order->user)->locale($order->user->preferred_locale ?? 'en')->send(new OrderShippedMail($order));
        } elseif ($nextStatus === 'delivered') {
            Mail::to($order->user)->locale($order->user->preferred_locale ?? 'en')->send(new OrderDeliveredMail($order));
        }

        return new OrderResource($order->fresh(['user', 'items.productVariant.product']));
    }

    public function cancel(Request $request, Order $order)
    {
        $this->authorize('cancel', $order);

        if (! $order->isCancellable()) {
            return response()->json(['message' => 'This order can no longer be cancelled.'], 422);
        }

        $order->update(['status' => 'cancelled']);

        SystemEvent::log(
            'order.cancelled',
            "Order {$order->order_number} cancelled by {$request->user()->name}.",
            $request->user()->name,
            'user',
            ['order_id' => $order->id],
        );

        return new OrderResource($order->fresh(['user', 'items.productVariant.product']));
    }

    /**
     * Refund a paid order's captured PayPal payment in full. Admin-only, and
     * only possible while the order is still marked 'paid' — an order that's
     * unpaid, already refunded, or cancelled has nothing to refund.
     */
    public function refund(Request $request, Order $order, PayPalClient $payPal)
    {
        $this->authorize('refund', $order);

        if ($order->payment_status !== 'paid') {
            return response()->json(['message' => 'Only paid orders can be refunded.'], 422);
        }

        if (! $order->paypal_transaction_id) {
            return response()->json(['message' => 'This order has no captured PayPal payment to refund.'], 422);
        }

        try {
            $payPal->refundCapture($order->paypal_transaction_id);
        } catch (RuntimeException $e) {
            report($e);

            return response()->json(['message' => 'PayPal refund failed. Please try again.'], 502);
        }

        $order->update([
            'status' => 'refunded',
            'payment_status' => 'refunded',
        ]);

        SystemEvent::log(
            'order.refunded',
            "Order {$order->order_number} refunded by {$request->user()->name}.",
            $request->user()->name,
            'user',
            ['order_id' => $order->id],
        );

        $order->refresh()->loadMissing('user');

        Mail::to($order->user)->locale($order->user->preferred_locale ?? 'en')->send(new OrderRefundedMail($order));

        return new OrderResource($order->fresh(['user', 'items.productVariant.product']));
    }

    public function invoice(Request $request, Order $order, InvoiceService $invoices)
    {
        $this->authorize('view', $order);

        return $invoices->generate($order)->stream("invoice-{$order->order_number}.pdf");
    }
}
