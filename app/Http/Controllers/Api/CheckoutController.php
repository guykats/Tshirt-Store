<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidCouponException;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Mail\OrderConfirmationMail;
use App\Models\Address;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\SystemEvent;
use App\Models\User;
use App\Notifications\LowStockAlert;
use App\Services\CouponService;
use App\Services\PayPalClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class CheckoutController extends Controller
{
    public function __construct(protected PayPalClient $payPal, protected CouponService $coupons) {}

    /**
     * Create a local order plus a matching PayPal order for the buyer to approve.
     *
     * Reachable without an authenticated session (see routes/api.php) so
     * shoppers aren't forced to register before buying. A guest only needs
     * to provide an email; behind the scenes we create a normal User row
     * for them (random, never-shared password, `is_guest` flag set) and
     * log them into it for the rest of the request/session, so every
     * existing user_id-based check (OrderPolicy, OrderController,
     * InvoiceService, OrderConfirmationMail) keeps working completely
     * unmodified. If the email already belongs to an existing account we
     * refuse to silently attach the order to it — that would let anyone
     * checkout as a stranger's real account and, since we log the buyer
     * in, would hand them a session for it.
     */
    public function store(Request $request)
    {
        // Whether this request arrived with a real, already-authenticated
        // session *before* any guest account auto-creation below — only
        // that case is ever allowed to reference an existing saved address,
        // since a guest has no addresses on file yet at this point in the
        // request.
        $loggedInBuyer = $request->user();

        $rules = [
            'product_variant_id' => ['required', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:20'],
            // A logged-in customer may pass an existing saved address instead
            // of a full inline shipping_address (see AddressController) — the
            // frontend is responsible for defaulting the selection to the
            // buyer's is_default address; the backend deliberately does not
            // silently substitute one on the caller's behalf when neither is
            // sent, so an order never lands on an address the caller didn't
            // explicitly choose.
            'shipping_address_id' => ['nullable', 'integer', 'exists:addresses,id'],
            'shipping_address' => ['nullable', 'array'],
            'shipping_address.full_name' => ['required_with:shipping_address', 'string', 'max:255'],
            'shipping_address.line1' => ['required_with:shipping_address', 'string', 'max:255'],
            'shipping_address.line2' => ['nullable', 'string', 'max:255'],
            'shipping_address.city' => ['required_with:shipping_address', 'string', 'max:100'],
            'shipping_address.state' => ['required_with:shipping_address', 'string', 'max:100'],
            'shipping_address.postal_code' => ['required_with:shipping_address', 'string', 'max:20'],
            'shipping_address.country' => ['nullable', 'string', 'size:2'],
            'shipping_address.phone' => ['nullable', 'string', 'max:50'],
            'code' => ['nullable', 'string', 'max:50'],
        ];

        if (! $loggedInBuyer) {
            $rules['email'] = ['required', 'email', 'max:255'];
        }

        $data = $request->validate($rules);

        if (empty($data['shipping_address_id']) && empty($data['shipping_address'])) {
            throw ValidationException::withMessages([
                'shipping_address' => 'A shipping address is required.',
            ]);
        }

        $existingAddress = null;

        if (! empty($data['shipping_address_id'])) {
            if (! $loggedInBuyer) {
                return response()->json([
                    'message' => 'Only signed-in customers can check out with a saved address.',
                ], 422);
            }

            $existingAddress = Address::find($data['shipping_address_id']);

            if (! $existingAddress || $existingAddress->user_id !== $loggedInBuyer->id) {
                return response()->json([
                    'message' => 'That address does not belong to your account.',
                ], 403);
            }
        }

        $buyer = $request->user();

        if (! $buyer) {
            if (User::where('email', $data['email'])->exists()) {
                return response()->json([
                    'message' => 'An account already exists for this email. Please log in to continue checkout.',
                ], 409);
            }

            $buyer = User::create([
                'name' => $data['shipping_address']['full_name'],
                'email' => $data['email'],
                'password' => Str::random(40),
                'is_guest' => true,
            ]);

            Auth::login($buyer);
            $request->session()->regenerate();
        }

        $variant = ProductVariant::with('product')->findOrFail($data['product_variant_id']);

        if ($variant->product->status !== 'active') {
            return response()->json(['message' => 'This product is not currently available.'], 422);
        }

        if ($variant->stock_quantity < $data['quantity']) {
            return response()->json(['message' => 'Not enough stock for the requested quantity.'], 422);
        }

        $unitPrice = (float) ($variant->price_override ?? $variant->product->base_price);
        $subtotal = round($unitPrice * $data['quantity'], 2);
        $currency = $variant->product->currency;

        $variantToAlert = null;

        try {
            $order = DB::transaction(function () use ($buyer, $data, $variant, $unitPrice, $subtotal, $currency, $existingAddress, &$variantToAlert) {
                $locked = ProductVariant::where('id', $variant->id)->lockForUpdate()->firstOrFail();

                if ($locked->stock_quantity < $data['quantity']) {
                    throw new InsufficientStockException();
                }

                $locked->decrement('stock_quantity', $data['quantity']);

                // Only alert the first time this variant crosses the
                // threshold — low_stock_alerted_at is cleared again once an
                // admin restocks it above the threshold (see
                // Admin\ProductVariantController::update), so a variant that
                // repeatedly sells out doesn't spam an alert on every order.
                if (
                    $locked->stock_quantity <= InventoryController::DEFAULT_THRESHOLD
                    && $locked->low_stock_alerted_at === null
                ) {
                    $locked->forceFill(['low_stock_alerted_at' => now()])->save();
                    $variantToAlert = $locked;
                }

                $discountCode = null;
                $discountAmount = 0.0;

                if (! empty($data['code'])) {
                    $coupon = $this->coupons->lockAndValidate($data['code']);
                    $discountAmount = $this->coupons->discountFor($coupon, $subtotal);
                    $discountCode = $coupon->code;
                    $coupon->increment('redemptions_count');
                }

                $totalAmount = round($subtotal - $discountAmount, 2);

                // Reuse an existing saved address (no new addresses row) when
                // the buyer picked one; otherwise create a fresh one exactly
                // as before this feature existed, for both a logged-in
                // customer entering a brand-new address and every guest.
                $address = $existingAddress ?? $buyer->addresses()->create([
                    'type' => 'shipping',
                    ...$data['shipping_address'],
                ]);

                $order = $buyer->orders()->create([
                    'order_number' => 'ORD-'.now()->format('ymd').'-'.strtoupper(Str::random(6)),
                    'subtotal' => $subtotal,
                    'discount_code' => $discountCode,
                    'discount_amount' => $discountAmount,
                    'total_amount' => $totalAmount,
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
        } catch (InsufficientStockException) {
            return response()->json(['message' => 'Not enough stock for the requested quantity.'], 422);
        } catch (InvalidCouponException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        if ($variantToAlert !== null) {
            try {
                Notification::send(User::where('role', 'admin')->get(), new LowStockAlert($variantToAlert));
            } catch (\Throwable $e) {
                report($e);
                Log::warning('Low stock alert failed to send.', ['product_variant_id' => $variantToAlert->id]);
            }
        }

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

        $order->refresh();

        SystemEvent::log(
            'order.paid',
            "Order {$order->order_number} paid via PayPal.",
            $request->user()->name,
            'user',
            ['order_id' => $order->id, 'paypal_transaction_id' => $captureId],
        );

        try {
            Mail::to($order->user)->locale($order->user->preferred_locale ?? 'en')->send(new OrderConfirmationMail($order));
        } catch (\Throwable $e) {
            report($e);
            Log::warning('Order confirmation email failed to send after successful payment.', ['order_id' => $order->id]);
        }

        return new OrderResource($order->fresh(['items.productVariant']));
    }
}
