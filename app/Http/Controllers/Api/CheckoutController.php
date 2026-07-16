<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Services\PayPalClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class CheckoutController extends Controller
{
    public function __construct(protected PayPalClient $payPal) {}

    /**
     * Create a local order plus a matching PayPal order for the buyer to approve.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'product_variant_id' => ['required', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:20'],
            'shipping_address' => ['required', 'array'],
            'shipping_address.full_name' => ['required', 'string', 'max:255'],
            'shipping_address.line1' => ['required', 'string', 'max:255'],
            'shipping_address.line2' => ['nullable', 'string', 'max:255'],
            'shipping_address.city' => ['required', 'string', 'max:100'],
            'shipping_address.state' => ['required', 'string', 'max:100'],
            'shipping_address.postal_code' => ['required', 'string', 'max:20'],
            'shipping_address.country' => ['nullable', 'string', 'size:2'],
            'shipping_address.phone' => ['nullable', 'string', 'max:50'],
        ]);

        $variant = ProductVariant::with('product')->findOrFail($data['product_variant_id']);

        if ($variant->stock_quantity < $data['quantity']) {
            return response()->json(['message' => 'Not enough stock for the requested quantity.'], 422);
        }

        $unitPrice = (float) ($variant->price_override ?? $variant->product->base_price);
        $subtotal = round($unitPrice * $data['quantity'], 2);
        $currency = $variant->product->currency;

        $order = DB::transaction(function () use ($request, $data, $variant, $unitPrice, $subtotal, $currency) {
            $address = $request->user()->addresses()->create([
                'type' => 'shipping',
                ...$data['shipping_address'],
            ]);

            $order = $request->user()->orders()->create([
                'order_number' => 'ORD-'.now()->format('ymd').'-'.strtoupper(Str::random(6)),
                'subtotal' => $subtotal,
                'total_amount' => $subtotal,
                'currency' => $currency,
                'shipping_address_id' => $address->id,
                'billing_address_id' => $address->id,
            ]);

            $order->items()->create([
                'product_variant_id' => $variant->id,
                'quantity' => $data['quantity'],
                'unit_price' => $unitPrice,
                'subtotal' => $subtotal,
            ]);

            return $order;
        });

        try {
            $payPalOrder = $this->payPal->createOrder($order->order_number, (float) $order->total_amount, $currency);
        } catch (RuntimeException $e) {
            report($e);

            return response()->json(['message' => 'Unable to start PayPal checkout right now. Please try again shortly.'], 502);
        }

        $order->update(['paypal_order_id' => $payPalOrder['id']]);

        return response()->json([
            'order' => new OrderResource($order->fresh(['items.productVariant'])),
            'paypal_order_id' => $payPalOrder['id'],
        ], 201);
    }

    /**
     * Capture payment for a PayPal order the buyer has just approved in the PayPal button flow.
     */
    public function capture(Request $request, Order $order)
    {
        $this->authorize('capture', $order);

        if (! $order->paypal_order_id) {
            return response()->json(['message' => 'This order has no associated PayPal order.'], 422);
        }

        if ($order->payment_status === 'paid') {
            return new OrderResource($order->load(['items.productVariant']));
        }

        try {
            $capture = $this->payPal->captureOrder($order->paypal_order_id);
        } catch (RuntimeException $e) {
            report($e);

            return response()->json(['message' => 'Payment capture failed. Please try again.'], 502);
        }

        $status = $capture['status'] ?? null;
        $captureId = $capture['purchase_units'][0]['payments']['captures'][0]['id'] ?? null;

        if ($status !== 'COMPLETED') {
            $order->update(['payment_status' => 'failed']);

            return response()->json(['message' => 'PayPal did not confirm payment.', 'status' => $status], 422);
        }

        $order->update([
            'payment_status' => 'paid',
            'paypal_transaction_id' => $captureId,
        ]);

        return new OrderResource($order->fresh(['items.productVariant']));
    }
}
