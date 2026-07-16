<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\SystemEvent;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Order::class);

        $query = Order::query()->with(['user', 'items.productVariant']);

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

        return new OrderResource($order->load(['user', 'shippingAddress', 'billingAddress', 'items.productVariant']));
    }

    public function approve(Request $request, Order $order)
    {
        $this->authorize('update', $order);

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

        return new OrderResource($order->fresh(['user', 'items.productVariant']));
    }
}
