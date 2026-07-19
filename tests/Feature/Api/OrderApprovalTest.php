<?php

namespace Tests\Feature\Api;

use App\Models\Design;
use App\Models\Product;
use App\Models\SystemEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderApprovalTest extends TestCase
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

    public function test_order_history_includes_the_purchased_product_name(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $design = Design::create(['title' => 'Test Design', 'status' => 'approved']);
        $product = Product::create([
            'design_id' => $design->id, 'name' => 'Aleph Tee', 'slug' => 'aleph-tee-'.uniqid(),
            'base_price' => 32, 'sku' => 'AL-'.uniqid(), 'status' => 'active',
        ]);
        $variant = $product->variants()->create(['size' => 'M', 'color' => 'Black', 'sku' => 'AL-M-'.uniqid(), 'stock_quantity' => 5]);
        $order = $this->makeOrder($customer);
        $order->items()->create(['product_variant_id' => $variant->id, 'quantity' => 1, 'unit_price' => 32, 'subtotal' => 32]);

        $response = $this->actingAs($customer)->getJson('/api/orders');

        $response->assertOk()->assertJsonPath('data.0.items.0.product_variant.product.name', 'Aleph Tee');
    }

    public function test_a_customer_only_sees_their_own_orders(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $otherCustomer = User::factory()->create(['role' => 'customer']);
        $this->makeOrder($customer);
        $this->makeOrder($otherCustomer);

        $response = $this->actingAs($customer)->getJson('/api/orders');

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_an_admin_sees_all_orders(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $this->makeOrder($customer);
        $this->makeOrder($admin);

        $response = $this->actingAs($admin)->getJson('/api/orders');

        $response->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_a_customer_cannot_view_another_customers_order(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $stranger = User::factory()->create(['role' => 'customer']);
        $order = $this->makeOrder($customer);

        $this->actingAs($stranger)->getJson("/api/orders/{$order->id}")->assertForbidden();
    }

    public function test_customers_cannot_approve_orders(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->makeOrder($customer);

        $this->actingAs($customer)->postJson("/api/orders/{$order->id}/approve")->assertForbidden();
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'pending_approval']);
    }

    public function test_an_admin_can_approve_an_order_and_it_is_logged(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Approving Admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->makeOrder($customer);

        $response = $this->actingAs($admin)->postJson("/api/orders/{$order->id}/approve");

        $response->assertOk()->assertJsonPath('data.status', 'approved');
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'approved', 'approved_by' => $admin->id]);
        $this->assertDatabaseHas('system_events', [
            'event_type' => 'order.approved',
            'actor_name' => 'Approving Admin',
        ]);
    }

    public function test_approving_an_already_approved_order_does_not_duplicate_the_audit_log(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->makeOrder($customer);

        $this->actingAs($admin)->postJson("/api/orders/{$order->id}/approve")->assertOk();
        $this->actingAs($admin)->postJson("/api/orders/{$order->id}/approve")->assertOk();

        $this->assertSame(1, SystemEvent::where('event_type', 'order.approved')->count());
    }

    public function test_the_owner_can_download_their_own_invoice(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->makeOrder($customer);

        $response = $this->actingAs($customer)->get("/api/orders/{$order->id}/invoice");

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('content-type'));
    }

    public function test_a_stranger_cannot_download_someone_elses_invoice(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $stranger = User::factory()->create(['role' => 'customer']);
        $order = $this->makeOrder($customer);

        $this->actingAs($stranger)->get("/api/orders/{$order->id}/invoice")->assertForbidden();
    }

    public function test_order_resource_exposes_tracking_details_once_set(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->makeOrder($customer, [
            'status' => 'shipped',
            'tracking_number' => '1Z999AA10123456784',
            'carrier' => 'UPS',
        ]);

        $response = $this->actingAs($customer)->getJson("/api/orders/{$order->id}");

        $response->assertOk()
            ->assertJsonPath('data.tracking_number', '1Z999AA10123456784')
            ->assertJsonPath('data.carrier', 'UPS')
            ->assertJsonPath('data.tracking_url', 'https://www.ups.com/track?loc=en_US&tracknum=1Z999AA10123456784');
    }

    public function test_order_resource_omits_tracking_url_for_an_unset_or_unrecognized_carrier(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $orderWithoutTracking = $this->makeOrder($customer);

        $this->actingAs($customer)->getJson("/api/orders/{$orderWithoutTracking->id}")
            ->assertOk()
            ->assertJsonPath('data.tracking_number', null)
            ->assertJsonPath('data.tracking_url', null);

        $orderWithUnknownCarrier = $this->makeOrder($customer, [
            'tracking_number' => 'ABC123',
            'carrier' => 'Some Regional Courier',
        ]);

        $this->actingAs($customer)->getJson("/api/orders/{$orderWithUnknownCarrier->id}")
            ->assertOk()
            ->assertJsonPath('data.tracking_number', 'ABC123')
            ->assertJsonPath('data.tracking_url', null);
    }

    public function test_the_invoice_view_includes_tracking_details_once_set(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->makeOrder($customer, [
            'status' => 'shipped',
            'tracking_number' => '1Z999AA10123456784',
            'carrier' => 'UPS',
        ]);
        $order->load(['billingAddress', 'shippingAddress', 'items.productVariant.product', 'user']);

        $html = view('invoices.order', ['order' => $order])->render();

        $this->assertStringContainsString('1Z999AA10123456784', $html);
        $this->assertStringContainsString('UPS', $html);
    }

    public function test_the_invoice_view_omits_tracking_details_when_unset(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->makeOrder($customer);
        $order->load(['billingAddress', 'shippingAddress', 'items.productVariant.product', 'user']);

        $html = view('invoices.order', ['order' => $order])->render();

        $this->assertStringNotContainsString(__('invoice.tracking_number'), $html);
    }
}
