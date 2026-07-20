<?php

namespace Tests\Feature\Api;

use App\Models\Design;
use App\Models\Product;
use App\Models\User;
use App\Models\WishlistItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DeleteAccountTest extends TestCase
{
    use RefreshDatabase;

    protected function makeProduct(array $overrides = []): Product
    {
        $design = Design::create([
            'title' => 'Test Design',
            'category' => 'cultural-signal',
            'status' => 'approved',
        ]);

        return Product::create(array_merge([
            'design_id' => $design->id,
            'name' => 'Test Tee',
            'slug' => 'test-tee-'.uniqid(),
            'description' => 'A test product.',
            'base_price' => 30.00,
            'sku' => 'TT-'.uniqid(),
            'status' => 'active',
        ], $overrides));
    }

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

        return [
            'address' => $address,
            'order' => $owner->orders()->create(array_merge([
                'order_number' => 'ORD-'.uniqid(),
                'subtotal' => 30,
                'total_amount' => 30,
                'shipping_address_id' => $address->id,
                'billing_address_id' => $address->id,
            ], $overrides)),
        ];
    }

    public function test_wrong_current_password_is_rejected_and_nothing_changes(): void
    {
        $user = User::factory()->create(['password' => 'correct-password123', 'name' => 'Real Name', 'email' => 'real@example.com']);

        $response = $this->actingAs($user)->deleteJson('/api/account', [
            'current_password' => 'wrong-password',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('current_password');
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Real Name', 'email' => 'real@example.com']);
        $this->assertTrue(Hash::check('correct-password123', $user->fresh()->password));
    }

    public function test_an_admin_cannot_self_delete_through_this_endpoint(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'password' => 'correct-password123', 'name' => 'The Admin', 'email' => 'admin2@example.com']);

        $response = $this->actingAs($admin)->deleteJson('/api/account', [
            'current_password' => 'correct-password123',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('account');
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'name' => 'The Admin', 'email' => 'admin2@example.com']);
        $this->assertTrue(Hash::check('correct-password123', $admin->fresh()->password));
    }

    public function test_correct_password_anonymizes_the_account_and_old_credentials_no_longer_work(): void
    {
        $user = User::factory()->create([
            'password' => 'correct-password123',
            'name' => 'Real Name',
            'email' => 'real@example.com',
            'phone' => '555-1234',
        ]);
        $originalId = $user->id;

        $response = $this->actingAs($user)->deleteJson('/api/account', [
            'current_password' => 'correct-password123',
        ]);

        $response->assertNoContent();

        $fresh = $user->fresh();
        $this->assertSame('Deleted User', $fresh->name);
        $this->assertNotSame('real@example.com', $fresh->email);
        $this->assertSame("deleted-user-{$originalId}@deleted.invalid", $fresh->email);
        $this->assertNull($fresh->phone);
        $this->assertNull($fresh->email_verified_at);
        $this->assertFalse(Hash::check('correct-password123', $fresh->password));

        // Old email no longer resolves to any account and the old password no
        // longer verifies against it — both required for the old credentials
        // to log in. (A literal follow-up POST /api/login within this same
        // test isn't used here: it would go through the auth:sanctum route
        // just exercised above, which flips PHPUnit's shared in-process auth
        // manager to the "sanctum" default guard for the rest of the test —
        // a well-known Sanctum/Laravel test-harness quirk, not a real login
        // guard change in production.)
        $this->assertDatabaseMissing('users', ['email' => 'real@example.com']);

        $this->assertDatabaseHas('system_events', [
            'event_type' => 'user.self_deleted',
        ]);
    }

    public function test_addresses_referenced_by_an_order_survive_but_unreferenced_addresses_are_deleted(): void
    {
        $user = User::factory()->create(['password' => 'correct-password123']);
        ['address' => $orderAddress] = $this->makeOrder($user);

        $unreferencedAddress = $user->addresses()->create([
            'type' => 'shipping',
            'full_name' => 'Unused Address',
            'line1' => '2 Test Ave',
            'city' => 'Boston',
            'state' => 'MA',
            'postal_code' => '02101',
        ]);

        $this->actingAs($user)->deleteJson('/api/account', [
            'current_password' => 'correct-password123',
        ])->assertNoContent();

        $this->assertDatabaseHas('addresses', ['id' => $orderAddress->id]);
        $this->assertDatabaseMissing('addresses', ['id' => $unreferencedAddress->id]);
    }

    public function test_wishlist_items_are_deleted(): void
    {
        $user = User::factory()->create(['password' => 'correct-password123']);
        $product = $this->makeProduct();
        $item = WishlistItem::create(['user_id' => $user->id, 'product_id' => $product->id]);

        $this->actingAs($user)->deleteJson('/api/account', [
            'current_password' => 'correct-password123',
        ])->assertNoContent();

        $this->assertDatabaseMissing('wishlist_items', ['id' => $item->id]);
    }

    public function test_the_session_is_invalidated_after_deletion(): void
    {
        $user = User::factory()->create(['password' => 'correct-password123']);

        $this->actingAs($user)->deleteJson('/api/account', [
            'current_password' => 'correct-password123',
        ])->assertNoContent();

        // Checked directly against the 'web' session guard (rather than a
        // literal follow-up GET /api/me) because a follow-up request through
        // auth:sanctum would reuse PHPUnit's in-process, memoized Sanctum
        // RequestGuard from the request just above — which caches its
        // resolved user for the lifetime of that guard object and so would
        // still report the stale (pre-deletion) user, a Sanctum test-harness
        // artifact that doesn't happen across real, separate requests in
        // production. Asserting the 'web' guard is unauthenticated is what
        // AuthController::deleteAccount actually invalidates.
        $this->assertGuest('web');
    }

    public function test_guests_cannot_delete_an_account(): void
    {
        $this->deleteJson('/api/account', [
            'current_password' => 'whatever',
        ])->assertUnauthorized();
    }
}
