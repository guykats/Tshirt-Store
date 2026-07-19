<?php

namespace Tests\Feature\Api;

use App\Mail\OrderRefundedMail;
use App\Models\SystemEvent;
use App\Models\User;
use App\Services\PayPalClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OrderRefundTest extends TestCase
{
    use RefreshDatabase;

    protected function makeOrder(User $owner, array $overrides = [])
    {
        $address = $owner->addresses()->create([
            'type' => 'shipping',
            'full_name' => 'Test Buyer',
            'line1' => '1 Test St',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
        ]);

        return $owner->orders()->create(array_merge([
            'order_number' => 'ORD-'.uniqid(),
            'subtotal' => 30,
            'total_amount' => 30,
            'shipping_address_id' => $address->id,
            'billing_address_id' => $address->id,
        ], $overrides));
    }

    public function test_an_admin_can_refund_a_paid_order(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Refunding Admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->makeOrder($customer, [
            'status' => 'approved',
            'payment_status' => 'paid',
            'paypal_transaction_id' => 'CAPTURE-123',
        ]);

        $this->mock(PayPalClient::class, function ($mock) {
            $mock->shouldReceive('refundCapture')->once()->with('CAPTURE-123')->andReturn(['status' => 'COMPLETED']);
        });

        $response = $this->actingAs($admin)->postJson("/api/orders/{$order->id}/refund");

        $response->assertOk()
            ->assertJsonPath('data.status', 'refunded')
            ->assertJsonPath('data.payment_status', 'refunded');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'refunded',
            'payment_status' => 'refunded',
        ]);

        $this->assertDatabaseHas('system_events', [
            'event_type' => 'order.refunded',
            'actor_name' => 'Refunding Admin',
        ]);

        Mail::assertSent(OrderRefundedMail::class, fn ($mail) => $mail->order->id === $order->id);
    }

    public function test_refunding_an_unpaid_order_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->makeOrder($customer, ['payment_status' => 'unpaid']);

        $this->mock(PayPalClient::class, function ($mock) {
            $mock->shouldNotReceive('refundCapture');
        });

        $response = $this->actingAs($admin)->postJson("/api/orders/{$order->id}/refund");

        $response->assertStatus(422);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_status' => 'unpaid']);
    }

    public function test_refunding_an_already_refunded_order_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->makeOrder($customer, [
            'status' => 'refunded',
            'payment_status' => 'refunded',
            'paypal_transaction_id' => 'CAPTURE-456',
        ]);

        $this->mock(PayPalClient::class, function ($mock) {
            $mock->shouldNotReceive('refundCapture');
        });

        $response = $this->actingAs($admin)->postJson("/api/orders/{$order->id}/refund");

        $response->assertStatus(422);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_status' => 'refunded']);
    }

    public function test_refunding_a_cancelled_order_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->makeOrder($customer, [
            'status' => 'cancelled',
            'payment_status' => 'unpaid',
        ]);

        $this->mock(PayPalClient::class, function ($mock) {
            $mock->shouldNotReceive('refundCapture');
        });

        $response = $this->actingAs($admin)->postJson("/api/orders/{$order->id}/refund");

        $response->assertStatus(422);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'cancelled']);
    }

    public function test_a_non_admin_cannot_refund_an_order(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->makeOrder($customer, [
            'status' => 'approved',
            'payment_status' => 'paid',
            'paypal_transaction_id' => 'CAPTURE-789',
        ]);

        $this->mock(PayPalClient::class, function ($mock) {
            $mock->shouldNotReceive('refundCapture');
        });

        $this->actingAs($customer)->postJson("/api/orders/{$order->id}/refund")->assertForbidden();
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_status' => 'paid']);
    }

    public function test_a_paypal_refund_failure_leaves_the_order_paid(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->makeOrder($customer, [
            'status' => 'approved',
            'payment_status' => 'paid',
            'paypal_transaction_id' => 'CAPTURE-FAIL',
        ]);

        $this->mock(PayPalClient::class, function ($mock) {
            $mock->shouldReceive('refundCapture')->once()->andThrow(new \RuntimeException('PayPal down'));
        });

        $response = $this->actingAs($admin)->postJson("/api/orders/{$order->id}/refund");

        $response->assertStatus(502);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_status' => 'paid']);
    }
}
