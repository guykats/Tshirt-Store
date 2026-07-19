<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PayPalClient
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.paypal.mode') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    protected function accessToken(): string
    {
        return Cache::remember('paypal.access_token', 3000, function () {
            $clientId = config('services.paypal.client_id');
            $clientSecret = config('services.paypal.client_secret');

            if (! $clientId || ! $clientSecret) {
                throw new RuntimeException('PayPal client_id/client_secret are not configured.');
            }

            $response = Http::asForm()
                ->withBasicAuth($clientId, $clientSecret)
                ->post("{$this->baseUrl}/v1/oauth2/token", [
                    'grant_type' => 'client_credentials',
                ])
                ->throw();

            return $response->json('access_token');
        });
    }

    protected function client()
    {
        return Http::withToken($this->accessToken())->acceptJson();
    }

    /**
     * Create a PayPal order for the given amount. Returns the raw PayPal order payload.
     */
    public function createOrder(string $orderNumber, float $amount, string $currency = 'USD'): array
    {
        try {
            $response = $this->client()->post("{$this->baseUrl}/v2/checkout/orders", [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => $orderNumber,
                    'amount' => [
                        'currency_code' => $currency,
                        'value' => number_format($amount, 2, '.', ''),
                    ],
                ]],
            ])->throw();
        } catch (RequestException $e) {
            throw new RuntimeException('PayPal createOrder failed: '.$e->response?->body(), previous: $e);
        }

        return $response->json();
    }

    /**
     * Capture a previously created and buyer-approved PayPal order. Returns the raw capture payload.
     */
    public function captureOrder(string $paypalOrderId): array
    {
        try {
            $response = $this->client()->post("{$this->baseUrl}/v2/checkout/orders/{$paypalOrderId}/capture")->throw();
        } catch (RequestException $e) {
            throw new RuntimeException('PayPal captureOrder failed: '.$e->response?->body(), previous: $e);
        }

        return $response->json();
    }

    public function getOrder(string $paypalOrderId): array
    {
        return $this->client()->get("{$this->baseUrl}/v2/checkout/orders/{$paypalOrderId}")->throw()->json();
    }

    /**
     * Refund a previously captured payment, in full or in part. Returns the raw
     * refund payload from PayPal. Pass $amount to issue a partial refund (in the
     * capture's original currency); omit it (the default) to refund the full
     * remaining captured amount.
     */
    public function refundCapture(string $captureId, ?float $amount = null, string $currency = 'USD'): array
    {
        $payload = [];

        if ($amount !== null) {
            $payload['amount'] = [
                'value' => number_format($amount, 2, '.', ''),
                'currency_code' => $currency,
            ];
        }

        try {
            $response = $this->client()->post("{$this->baseUrl}/v2/payments/captures/{$captureId}/refund", $payload)->throw();
        } catch (RequestException $e) {
            throw new RuntimeException('PayPal refundCapture failed: '.$e->response?->body(), previous: $e);
        }

        return $response->json();
    }

    /**
     * Verify an inbound webhook's signature via PayPal's verify-webhook-signature API.
     *
     * @param  array<string, string>  $headers  Expects transmission_id, transmission_time, cert_url, auth_algo, transmission_sig.
     */
    public function verifyWebhookSignature(array $headers, array $body): bool
    {
        $webhookId = config('services.paypal.webhook_id');

        if (! $webhookId) {
            return false;
        }

        $response = $this->client()->post("{$this->baseUrl}/v1/notifications/verify-webhook-signature", [
            'transmission_id' => $headers['transmission_id'] ?? null,
            'transmission_time' => $headers['transmission_time'] ?? null,
            'cert_url' => $headers['cert_url'] ?? null,
            'auth_algo' => $headers['auth_algo'] ?? null,
            'transmission_sig' => $headers['transmission_sig'] ?? null,
            'webhook_id' => $webhookId,
            'webhook_event' => $body,
        ]);

        return $response->json('verification_status') === 'SUCCESS';
    }
}
