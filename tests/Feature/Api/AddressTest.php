<?php

namespace Tests\Feature\Api;

use App\Models\Address;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddressTest extends TestCase
{
    use RefreshDatabase;

    protected function validAddress(array $overrides = []): array
    {
        return array_merge([
            'full_name' => 'Test Buyer',
            'line1' => '1 Test St',
            'line2' => '',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
            'country' => 'US',
            'phone' => '',
        ], $overrides);
    }

    public function test_a_guest_cannot_view_the_address_book(): void
    {
        $this->getJson('/api/account/addresses')->assertUnauthorized();
    }

    public function test_a_user_can_list_their_own_addresses(): void
    {
        $user = User::factory()->create();
        $user->addresses()->create(array_merge(['type' => 'shipping'], $this->validAddress()));

        $other = User::factory()->create();
        $other->addresses()->create(array_merge(['type' => 'shipping'], $this->validAddress()));

        $response = $this->actingAs($user)->getJson('/api/account/addresses');

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_a_users_first_saved_address_becomes_the_default_automatically(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/account/addresses', $this->validAddress());

        $response->assertCreated()->assertJsonPath('data.is_default', true);
    }

    public function test_a_second_saved_address_is_not_the_default(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('/api/account/addresses', $this->validAddress())->assertCreated();

        $response = $this->actingAs($user)->postJson('/api/account/addresses', $this->validAddress(['full_name' => 'Second Address']));

        $response->assertCreated()->assertJsonPath('data.is_default', false);
    }

    public function test_a_customer_cannot_view_another_customers_addresses_mixed_in(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $userA->addresses()->create(array_merge(['type' => 'shipping'], $this->validAddress()));

        $response = $this->actingAs($userB)->getJson('/api/account/addresses');

        $response->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_a_customer_cannot_update_another_customers_address(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $address = $owner->addresses()->create(array_merge(['type' => 'shipping'], $this->validAddress()));

        $response = $this->actingAs($stranger)->putJson("/api/account/addresses/{$address->id}", $this->validAddress(['full_name' => 'Hijacked']));

        $response->assertForbidden();
        $this->assertDatabaseHas('addresses', ['id' => $address->id, 'full_name' => 'Test Buyer']);
    }

    public function test_a_customer_cannot_delete_another_customers_address(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $address = $owner->addresses()->create(array_merge(['type' => 'shipping'], $this->validAddress()));

        $response = $this->actingAs($stranger)->deleteJson("/api/account/addresses/{$address->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('addresses', ['id' => $address->id]);
    }

    public function test_a_customer_cannot_set_another_customers_address_as_default(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $address = $owner->addresses()->create(array_merge(['type' => 'shipping', 'is_default' => false], $this->validAddress()));

        $response = $this->actingAs($stranger)->postJson("/api/account/addresses/{$address->id}/default");

        $response->assertForbidden();
        $this->assertDatabaseHas('addresses', ['id' => $address->id, 'is_default' => false]);
    }

    public function test_a_customer_can_update_their_own_address(): void
    {
        $user = User::factory()->create();
        $address = $user->addresses()->create(array_merge(['type' => 'shipping'], $this->validAddress()));

        $response = $this->actingAs($user)->putJson("/api/account/addresses/{$address->id}", $this->validAddress(['city' => 'Boston']));

        $response->assertOk()->assertJsonPath('data.city', 'Boston');
        $this->assertDatabaseHas('addresses', ['id' => $address->id, 'city' => 'Boston']);
    }

    public function test_a_customer_can_delete_their_own_unused_address(): void
    {
        $user = User::factory()->create();
        $address = $user->addresses()->create(array_merge(['type' => 'shipping'], $this->validAddress()));

        $response = $this->actingAs($user)->deleteJson("/api/account/addresses/{$address->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('addresses', ['id' => $address->id]);
    }

    public function test_an_address_referenced_by_an_order_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $address = $user->addresses()->create(array_merge(['type' => 'shipping'], $this->validAddress()));

        $user->orders()->create([
            'order_number' => 'ORD-ADDR-TEST',
            'subtotal' => 30,
            'total_amount' => 30,
            'shipping_address_id' => $address->id,
            'billing_address_id' => $address->id,
        ]);

        $response = $this->actingAs($user)->deleteJson("/api/account/addresses/{$address->id}");

        $response->assertStatus(422);
        $this->assertDatabaseHas('addresses', ['id' => $address->id]);
    }

    /**
     * Create 3 addresses for one user, set the second one as default, and
     * confirm exactly that one ends up is_default=true — never two, never
     * zero.
     */
    public function test_setting_an_address_as_default_flips_exactly_one_per_user(): void
    {
        $user = User::factory()->create();

        $first = $user->addresses()->create(array_merge(['type' => 'shipping', 'is_default' => true], $this->validAddress(['full_name' => 'First'])));
        $second = $user->addresses()->create(array_merge(['type' => 'shipping', 'is_default' => false], $this->validAddress(['full_name' => 'Second'])));
        $third = $user->addresses()->create(array_merge(['type' => 'shipping', 'is_default' => false], $this->validAddress(['full_name' => 'Third'])));

        $response = $this->actingAs($user)->postJson("/api/account/addresses/{$second->id}/default");

        $response->assertOk()->assertJsonPath('data.is_default', true);

        $this->assertDatabaseHas('addresses', ['id' => $first->id, 'is_default' => false]);
        $this->assertDatabaseHas('addresses', ['id' => $second->id, 'is_default' => true]);
        $this->assertDatabaseHas('addresses', ['id' => $third->id, 'is_default' => false]);

        $this->assertSame(1, Address::where('user_id', $user->id)->where('is_default', true)->count());
    }
}
