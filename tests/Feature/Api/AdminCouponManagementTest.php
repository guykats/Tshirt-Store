<?php

namespace Tests\Feature\Api;

use App\Models\Coupon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCouponManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function couponPayload(array $overrides = []): array
    {
        return array_merge([
            'code' => 'TESTCODE'.uniqid(),
            'type' => 'percent',
            'value' => 10,
            'expires_at' => null,
            'max_redemptions' => null,
            'active' => true,
        ], $overrides);
    }

    public function test_guests_cannot_manage_coupons(): void
    {
        $coupon = Coupon::create(['code' => 'GUEST1', 'type' => 'percent', 'value' => 10, 'active' => true]);

        $this->getJson('/api/admin/coupons')->assertUnauthorized();
        $this->postJson('/api/admin/coupons', $this->couponPayload())->assertUnauthorized();
        $this->getJson("/api/admin/coupons/{$coupon->id}")->assertUnauthorized();
        $this->putJson("/api/admin/coupons/{$coupon->id}", $this->couponPayload())->assertUnauthorized();
    }

    public function test_customers_cannot_manage_coupons(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $coupon = Coupon::create(['code' => 'CUST1', 'type' => 'percent', 'value' => 10, 'active' => true]);

        $this->actingAs($customer)->getJson('/api/admin/coupons')->assertForbidden();
        $this->actingAs($customer)->postJson('/api/admin/coupons', $this->couponPayload())->assertForbidden();
        $this->actingAs($customer)->getJson("/api/admin/coupons/{$coupon->id}")->assertForbidden();
        $this->actingAs($customer)->putJson("/api/admin/coupons/{$coupon->id}", $this->couponPayload())->assertForbidden();
    }

    public function test_an_admin_can_list_coupons(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Coupon::create(['code' => 'ALPHA', 'type' => 'percent', 'value' => 5, 'active' => true]);
        Coupon::create(['code' => 'BETA', 'type' => 'fixed', 'value' => 3, 'active' => false]);

        $response = $this->actingAs($admin)->getJson('/api/admin/coupons');

        $response->assertOk();
        $codes = collect($response->json('data'))->pluck('code');
        $this->assertTrue($codes->contains('ALPHA'));
        $this->assertTrue($codes->contains('BETA'));
    }

    public function test_an_admin_can_search_coupons_by_code(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Coupon::create(['code' => 'SAVE10', 'type' => 'percent', 'value' => 10, 'active' => true]);
        Coupon::create(['code' => 'FLAT5', 'type' => 'fixed', 'value' => 5, 'active' => true]);

        $response = $this->actingAs($admin)->getJson('/api/admin/coupons?search=save');

        $response->assertOk();
        $codes = collect($response->json('data'))->pluck('code');
        $this->assertTrue($codes->contains('SAVE10'));
        $this->assertFalse($codes->contains('FLAT5'));
    }

    public function test_an_admin_can_create_a_coupon(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->postJson('/api/admin/coupons', $this->couponPayload([
            'code' => 'newcode25',
            'type' => 'percent',
            'value' => 25,
        ]));

        $response->assertCreated()
            ->assertJsonPath('data.code', 'NEWCODE25')
            ->assertJsonPath('data.type', 'percent')
            ->assertJsonPath('data.value', 25)
            ->assertJsonPath('data.active', true);

        $this->assertDatabaseHas('coupons', ['code' => 'NEWCODE25']);
        $this->assertDatabaseHas('system_events', ['event_type' => 'coupon.created']);
    }

    public function test_creating_a_coupon_requires_required_fields(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->postJson('/api/admin/coupons', $this->couponPayload(['code' => '']));

        $response->assertStatus(422)->assertJsonValidationErrors('code');
    }

    public function test_creating_a_coupon_requires_a_valid_type(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->postJson('/api/admin/coupons', $this->couponPayload(['type' => 'not-a-type']));

        $response->assertStatus(422)->assertJsonValidationErrors('type');
    }

    public function test_creating_a_coupon_with_a_duplicate_code_is_rejected_case_insensitively(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Coupon::create(['code' => 'DUPE', 'type' => 'percent', 'value' => 10, 'active' => true]);

        $response = $this->actingAs($admin)->postJson('/api/admin/coupons', $this->couponPayload(['code' => 'dupe']));

        $response->assertStatus(422)->assertJsonValidationErrors('code');
    }

    public function test_an_admin_can_update_a_coupon(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $coupon = Coupon::create(['code' => 'UPDATEME', 'type' => 'percent', 'value' => 10, 'active' => true]);

        $response = $this->actingAs($admin)->putJson("/api/admin/coupons/{$coupon->id}", $this->couponPayload([
            'code' => 'UPDATEME',
            'type' => 'fixed',
            'value' => 7.5,
        ]));

        $response->assertOk()
            ->assertJsonPath('data.type', 'fixed')
            ->assertJsonPath('data.value', 7.5);

        $this->assertDatabaseHas('coupons', ['id' => $coupon->id, 'type' => 'fixed', 'value' => 7.5]);
        $this->assertDatabaseHas('system_events', ['event_type' => 'coupon.updated']);
    }

    public function test_an_admin_can_deactivate_a_coupon(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $coupon = Coupon::create(['code' => 'DEACTIVATE', 'type' => 'percent', 'value' => 10, 'active' => true]);

        $response = $this->actingAs($admin)->putJson("/api/admin/coupons/{$coupon->id}", $this->couponPayload([
            'code' => 'DEACTIVATE',
            'active' => false,
        ]));

        $response->assertOk()->assertJsonPath('data.active', false);
        $this->assertDatabaseHas('coupons', ['id' => $coupon->id, 'active' => false]);
    }

    public function test_an_admin_can_view_a_single_coupon(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $coupon = Coupon::create(['code' => 'VIEWME', 'type' => 'percent', 'value' => 10, 'active' => true]);

        $response = $this->actingAs($admin)->getJson("/api/admin/coupons/{$coupon->id}");

        $response->assertOk()->assertJsonPath('data.code', 'VIEWME');
    }
}
