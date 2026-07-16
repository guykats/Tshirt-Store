<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\PayPalClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PayPalWebhookController extends Controller
{
    public function __construct(protected PayPalClient $payPal) {}

    public function handle(Request $request)
    {
        $body = $request->json()->all();
        $eventType = $body['event_type'] ?? null;

        if (config('services.paypal.webhook_id')) {
            $verified = $this->payPal->verifyWebhookSignature([
                'transmission_id' => $request->header('PAYPAL-TRANSMISSION-ID'),
                'transmission_time' => $request->header('PAYPAL-TRANSMISSION-TIME'),
                'cert_url' => $request->header('PAYPAL-CERT-URL'),
                'auth_algo' => $request->header('PAYPAL-AUTH-ALGO'),
                'transmission_sig' => $request->header('PAYPAL-TRANSMISSION-SIG'),
            ], $body);

            if (! $verified) {
                Log::warning('PayPal webhook signature verification failed', ['event_type' => $eventType]);

                return response()->json(['message' => 'Invalid signature'], 401);
            }
        } else {
            Log::warning('PAYPAL_WEBHOOK_ID not configured; skipping signature verification', ['event_type' => $eventType]);
        }

        match ($eventType) {
            'PAYMENT.CAPTURE.COMPLETED' => $this->markPaid($body),
            'PAYMENT.CAPTURE.DENIED' => $this->markFailed($body),
            default => Log::info('Unhandled PayPal webhook event', ['event_type' => $eventType]),
        };

        return response()->json(['status' => 'ok']);
    }

    protected function markPaid(array $body): void
    {
        $resource = $body['resource'] ?? [];
        $paypalOrderId = $resource['supplementary_data']['related_ids']['order_id'] ?? null;
        $captureId = $resource['id'] ?? null;

        if (! $paypalOrderId) {
            return;
        }

        $order = Order::where('paypal_order_id', $paypalOrderId)->first();

        if ($order && $order->payment_status !== 'paid') {
            $order->update(['payment_status' => 'paid', 'paypal_transaction_id' => $captureId]);
        }
    }

    protected function markFailed(array $body): void
    {
        $resource = $body['resource'] ?? [];
        $paypalOrderId = $resource['supplementary_data']['related_ids']['order_id'] ?? null;

        if (! $paypalOrderId) {
            return;
        }

        Order::where('paypal_order_id', $paypalOrderId)
            ->where('payment_status', '!=', 'paid')
            ->update(['payment_status' => 'failed']);
    }
}
