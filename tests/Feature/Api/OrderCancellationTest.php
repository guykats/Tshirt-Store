<?php

namespace Tests\Feature\Api;

use App\Models\SystemEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderCancellationTest extends TestCase
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

    public function test_owner_can_cancel_a_cancellable_order_and_it_is_logged(): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'name' => 'Cancelling Customer']);
        $order = $this->makeOrder($customer);

        $response = $this->actingAs($customer)->postJson("/api/orders/{$order->id}/cancel");

        $response->assertOk()->assertJsonPath('data.status', 'cancelled');
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'cancelled']);
        $this->assertDatabaseHas('system_events', [
            'event_type' => 'order.cancelled',
            'actor_name' => 'Cancelling Customer',
        ]);
    }

    public function test_a_customer_cannot_cancel_another_customers_order(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $stranger = User::factory()->create(['role' => 'customer']);
        $order = $this->makeOrder($customer);

        $this->actingAs($stranger)->postJson("/api/orders/{$order->id}/cancel")->assertForbidden();
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'pending_approval']);
    }

    public function test_an_admin_cannot_cancel_a_customers_order_via_this_endpoint(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->makeOrder($customer);

        $this->actingAs($admin)->postJson("/api/orders/{$order->id}/cancel")->assertForbidden();
    }

    public function test_an_already_shipped_order_cannot_be_cancelled(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->makeOrder($customer, ['status' => 'shipped']);

        $response = $this->actingAs($customer)->postJson("/api/orders/{$order->id}/cancel");

        $response->assertStatus(422);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'shipped']);
        $this->assertSame(0, SystemEvent::where('event_type', 'order.cancelled')->count());
    }

    public function test_an_order_with_captured_payment_cannot_be_self_cancelled(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->makeOrder($customer, ['payment_status' => 'paid']);

        $response = $this->actingAs($customer)->postJson("/api/orders/{$order->id}/cancel");

        $response->assertStatus(422);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_status' => 'paid', 'status' => 'pending_approval']);
    }

    public function test_cancelling_an_already_cancelled_order_is_a_no_op_error(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->makeOrder($customer, ['status' => 'cancelled']);

        $this->actingAs($customer)->postJson("/api/orders/{$order->id}/cancel")->assertStatus(422);
    }
}
