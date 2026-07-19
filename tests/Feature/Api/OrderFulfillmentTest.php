<?php

namespace Tests\Feature\Api;

use App\Mail\OrderDeliveredMail;
use App\Mail\OrderShippedMail;
use App\Models\SystemEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OrderFulfillmentTest extends TestCase
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

    public function test_an_admin_can_advance_an_order_through_the_full_fulfillment_sequence(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Fulfillment Admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->makeOrder($customer, ['status' => 'pending_approval']);

        $this->actingAs($admin)->postJson("/api/orders/{$order->id}/advance-status")
            ->assertOk()->assertJsonPath('data.status', 'approved');
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'approved', 'approved_by' => $admin->id]);

        $this->actingAs($admin)->postJson("/api/orders/{$order->id}/advance-status")
            ->assertOk()->assertJsonPath('data.status', 'processing');
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'processing']);

        $this->actingAs($admin)->postJson("/api/orders/{$order->id}/advance-status", [
            'tracking_number' => '1Z999AA10123456784',
            'carrier' => 'UPS',
        ])->assertOk()->assertJsonPath('data.status', 'shipped');
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'shipped',
            'tracking_number' => '1Z999AA10123456784',
            'carrier' => 'UPS',
        ]);

        $this->actingAs($admin)->postJson("/api/orders/{$order->id}/advance-status")
            ->assertOk()->assertJsonPath('data.status', 'delivered');
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'delivered']);

        Mail::assertSent(OrderShippedMail::class, fn ($mail) => $mail->order->id === $order->id);
        Mail::assertSent(OrderDeliveredMail::class, fn ($mail) => $mail->order->id === $order->id);

        $this->assertSame(4, SystemEvent::where('event_type', 'order.status_advanced')->count());
    }

    public function test_advancing_a_delivered_order_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->makeOrder($customer, ['status' => 'delivered']);

        $this->actingAs($admin)->postJson("/api/orders/{$order->id}/advance-status")
            ->assertStatus(422);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'delivered']);
    }

    public function test_advancing_a_cancelled_order_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->makeOrder($customer, ['status' => 'cancelled']);

        $this->actingAs($admin)->postJson("/api/orders/{$order->id}/advance-status")
            ->assertStatus(422);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'cancelled']);
    }

    public function test_an_order_cannot_be_skipped_ahead_to_a_non_adjacent_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->makeOrder($customer, ['status' => 'approved']);

        $response = $this->actingAs($admin)->postJson("/api/orders/{$order->id}/advance-status", ['status' => 'delivered']);

        $response->assertStatus(422);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'approved']);
    }

    public function test_an_order_cannot_be_reversed_to_an_earlier_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->makeOrder($customer, ['status' => 'shipped']);

        $response = $this->actingAs($admin)->postJson("/api/orders/{$order->id}/advance-status", ['status' => 'processing']);

        $response->assertStatus(422);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'shipped']);
    }

    public function test_a_non_admin_cannot_advance_an_order_status(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->makeOrder($customer, ['status' => 'approved']);

        $this->actingAs($customer)->postJson("/api/orders/{$order->id}/advance-status")->assertForbidden();
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'approved']);
    }

    public function test_a_matching_target_status_is_accepted(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->makeOrder($customer, ['status' => 'processing']);

        $response = $this->actingAs($admin)->postJson("/api/orders/{$order->id}/advance-status", [
            'status' => 'shipped',
            'tracking_number' => '9400111899223344556677',
            'carrier' => 'USPS',
        ]);

        $response->assertOk()->assertJsonPath('data.status', 'shipped');
        Mail::assertSent(OrderShippedMail::class);
    }

    public function test_advancing_to_shipped_without_a_tracking_number_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->makeOrder($customer, ['status' => 'processing']);

        $response = $this->actingAs($admin)->postJson("/api/orders/{$order->id}/advance-status");

        $response->assertStatus(422)->assertJsonValidationErrors(['tracking_number', 'carrier']);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'processing']);
    }

    public function test_advancing_to_shipped_without_a_carrier_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->makeOrder($customer, ['status' => 'processing']);

        $response = $this->actingAs($admin)->postJson("/api/orders/{$order->id}/advance-status", [
            'tracking_number' => '1Z999AA10123456784',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['carrier']);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'processing']);
    }

    public function test_advancing_to_shipped_with_tracking_number_and_carrier_sends_mail_with_tracking_info(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->makeOrder($customer, ['status' => 'processing']);

        $response = $this->actingAs($admin)->postJson("/api/orders/{$order->id}/advance-status", [
            'tracking_number' => '1Z999AA10123456784',
            'carrier' => 'UPS',
        ]);

        $response->assertOk()->assertJsonPath('data.tracking_number', '1Z999AA10123456784')
            ->assertJsonPath('data.carrier', 'UPS')
            ->assertJsonPath('data.tracking_url', 'https://www.ups.com/track?loc=en_US&tracknum=1Z999AA10123456784');

        Mail::assertSent(OrderShippedMail::class, function (OrderShippedMail $mail) use ($order) {
            if ($mail->order->id !== $order->id) {
                return false;
            }

            $rendered = $mail->render();

            return str_contains($rendered, '1Z999AA10123456784')
                && str_contains($rendered, 'UPS')
                && str_contains($rendered, 'https://www.ups.com/track?loc=en_US&amp;tracknum=1Z999AA10123456784');
        });
    }

    public function test_advancing_to_shipped_with_an_unrecognized_carrier_has_no_tracking_link(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->makeOrder($customer, ['status' => 'processing']);

        $response = $this->actingAs($admin)->postJson("/api/orders/{$order->id}/advance-status", [
            'tracking_number' => 'ABC123',
            'carrier' => 'Some Regional Courier',
        ]);

        $response->assertOk()->assertJsonPath('data.tracking_url', null);

        Mail::assertSent(OrderShippedMail::class, function (OrderShippedMail $mail) use ($order) {
            if ($mail->order->id !== $order->id) {
                return false;
            }

            $rendered = $mail->render();

            return str_contains($rendered, 'ABC123')
                && str_contains($rendered, 'Some Regional Courier')
                && ! str_contains($rendered, 'href=');
        });
    }
}
