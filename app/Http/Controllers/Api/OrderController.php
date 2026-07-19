<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\SystemEvent;
use App\Services\InvoiceService;
use Illuminate\Http\Request;

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

    public function invoice(Request $request, Order $order, InvoiceService $invoices)
    {
        $this->authorize('view', $order);

        return $invoices->generate($order)->stream("invoice-{$order->order_number}.pdf");
    }
}
