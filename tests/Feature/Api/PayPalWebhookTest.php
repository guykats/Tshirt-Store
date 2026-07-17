<?php

namespace Tests\Feature\Api;

use App\Mail\OrderConfirmationMail;
use App\Models\Design;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\PayPalClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PayPalWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function makeVariant(): ProductVariant
    {
        $design = Design::create(['title' => 'Test Design', 'status' => 'approved']);

        $product = Product::create([
            'design_id' => $design->id,
            'name' => 'Test Tee',
            'slug' => 'test-tee-'.uniqid(),
            'base_price' => 30.00,
            'sku' => 'TT-'.uniqid(),
            'status' => 'active',
        ]);

        return $product->variants()->create([
            'size' => 'M',
            'color' => 'Black',
            'sku' => 'TT-M-BLK-'.uniqid(),
            'stock_quantity' => 10,
        ]);
    }

    public function test_webhook_is_rejected_when_signature_cannot_be_verified(): void
    {
        $this->mock(PayPalClient::class, function ($mock) {
            $mock->shouldReceive('verifyWebhookSignature')->once()->andReturn(false);
        });

        $this->postJson('/api/webhooks/paypal', [
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
            'resource' => [],
        ])->assertStatus(401);
    }

    public function test_a_verified_completed_capture_marks_the_order_paid(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $variant = $this->makeVariant();
        $address = $user->addresses()->create([
            'type' => 'shipping', 'full_name' => 'Test Buyer', 'line1' => '1 Test St',
            'city' => 'NY', 'state' => 'NY', 'postal_code' => '10001',
        ]);

        $order = $user->orders()->create([
            'order_number' => 'ORD-WEBHOOK-1',
            'subtotal' => 30,
            'total_amount' => 30,
            'paypal_order_id' => 'PAYPAL-WEBHOOK-1',
            'shipping_address_id' => $address->id,
            'billing_address_id' => $address->id,
        ]);
        $order->items()->create([
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'unit_price' => 30,
            'subtotal' => 30,
        ]);

        $this->mock(PayPalClient::class, function ($mock) {
            $mock->shouldReceive('verifyWebhookSignature')->once()->andReturn(true);
        });

        $this->postJson('/api/webhooks/paypal', [
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
            'resource' => [
                'id' => 'CAPTURE-WEBHOOK-1',
                'supplementary_data' => ['related_ids' => ['order_id' => 'PAYPAL-WEBHOOK-1']],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'payment_status' => 'paid',
            'paypal_transaction_id' => 'CAPTURE-WEBHOOK-1',
        ]);
        Mail::assertSent(OrderConfirmationMail::class);
    }
}
