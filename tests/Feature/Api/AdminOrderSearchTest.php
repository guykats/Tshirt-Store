<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrderSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function makeOrder(User $owner, array $overrides = [])
    {
        $address = $owner->addresses()->create([
            'type' => 'shipping',
            'full_name' => $owner->name,
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

    public function test_an_admin_can_search_orders_by_partial_customer_name(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $sarah = User::factory()->create(['role' => 'customer', 'name' => 'Sarah Cohen', 'email' => 'sarah@example.com']);
        $david = User::factory()->create(['role' => 'customer', 'name' => 'David Levi', 'email' => 'david@example.com']);
        $sarahOrder = $this->makeOrder($sarah);
        $this->makeOrder($david);

        $response = $this->actingAs($admin)->getJson('/api/orders?search=sarah');

        $response->assertOk()->assertJsonCount(1, 'data');
        $this->assertSame($sarahOrder->id, $response->json('data.0.id'));
    }

    public function test_an_admin_can_search_orders_by_partial_customer_email(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $sarah = User::factory()->create(['role' => 'customer', 'name' => 'Sarah Cohen', 'email' => 'sarah@example.com']);
        $david = User::factory()->create(['role' => 'customer', 'name' => 'David Levi', 'email' => 'david@example.com']);
        $sarahOrder = $this->makeOrder($sarah);
        $this->makeOrder($david);

        $response = $this->actingAs($admin)->getJson('/api/orders?search=SARAH@EXAMPLE');

        $response->assertOk()->assertJsonCount(1, 'data');
        $this->assertSame($sarahOrder->id, $response->json('data.0.id'));
    }

    public function test_search_results_still_paginate(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        for ($i = 0; $i < 25; $i++) {
            $customer = User::factory()->create(['role' => 'customer', 'name' => "Matching Customer {$i}", 'email' => "matching{$i}@example.com"]);
            $this->makeOrder($customer);
        }

        $other = User::factory()->create(['role' => 'customer', 'name' => 'Unrelated Person', 'email' => 'unrelated@example.com']);
        $this->makeOrder($other);

        $firstPage = $this->actingAs($admin)->getJson('/api/orders?search=matching');
        $firstPage->assertOk()
            ->assertJsonCount(20, 'data')
            ->assertJsonPath('meta.total', 25)
            ->assertJsonPath('meta.last_page', 2);

        $secondPage = $this->actingAs($admin)->getJson('/api/orders?search=matching&page=2');
        $secondPage->assertOk()->assertJsonCount(5, 'data');
    }

    /**
     * The admin `search` param is only honored on the admin branch of
     * index() (mirrors how `status` already behaved before this change) — a
     * non-admin's results stay scoped to their own user_id no matter what
     * they pass, so search can never be used to find another customer's
     * order.
     */
    public function test_a_customer_cannot_use_search_to_see_another_customers_orders(): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'name' => 'Own Customer']);
        $stranger = User::factory()->create(['role' => 'customer', 'name' => 'Stranger Person', 'email' => 'stranger@example.com']);
        $ownOrder = $this->makeOrder($customer);
        $strangerOrder = $this->makeOrder($stranger);

        $response = $this->actingAs($customer)->getJson('/api/orders?search=stranger');

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($ownOrder->id));
        $this->assertFalse($ids->contains($strangerOrder->id));
    }

    public function test_a_search_with_no_matches_returns_an_empty_paginated_result_without_erroring(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer', 'name' => 'Real Customer', 'email' => 'real@example.com']);
        $this->makeOrder($customer);

        $response = $this->actingAs($admin)->getJson('/api/orders?search=nonexistent-shopper');

        $response->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 0);
    }
}
