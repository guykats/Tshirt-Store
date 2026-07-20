<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function makeGuestUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'email' => 'guest-buyer@example.com',
            'password' => Str::random(40),
            'is_guest' => true,
        ], $overrides));
    }

    public function test_registering_with_a_guest_email_claims_the_existing_row(): void
    {
        $guest = $this->makeGuestUser();

        $response = $this->postJson('/api/register', [
            'name' => 'Real Name',
            'email' => 'guest-buyer@example.com',
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $response->assertCreated()->assertJsonPath('data.id', $guest->id);
        $this->assertSame(1, User::where('email', 'guest-buyer@example.com')->count());

        $guest->refresh();
        $this->assertFalse($guest->is_guest);
        $this->assertSame('Real Name', $guest->name);
        $this->assertTrue(Hash::check('new-password123', $guest->password));
    }

    public function test_claiming_a_guest_account_preserves_its_prior_orders(): void
    {
        $guest = $this->makeGuestUser();

        $address = $guest->addresses()->create([
            'type' => 'shipping',
            'full_name' => 'Guest Buyer',
            'line1' => '1 Test St',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
        ]);

        $guest->orders()->create([
            'order_number' => 'ORD-'.uniqid(),
            'subtotal' => 30,
            'total_amount' => 30,
            'shipping_address_id' => $address->id,
            'billing_address_id' => $address->id,
        ]);

        $ordersBefore = $guest->orders()->count();

        $this->postJson('/api/register', [
            'name' => 'Real Name',
            'email' => 'guest-buyer@example.com',
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ])->assertCreated();

        $guest->refresh();
        $this->assertSame($ordersBefore, $guest->orders()->count());
    }

    public function test_registering_with_an_email_already_tied_to_a_real_account_is_still_rejected(): void
    {
        User::factory()->create(['email' => 'real-customer@example.com', 'is_guest' => false]);

        $response = $this->postJson('/api/register', [
            'name' => 'Someone Else',
            'email' => 'real-customer@example.com',
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('email');
        $this->assertSame(1, User::where('email', 'real-customer@example.com')->count());
    }

    public function test_a_freshly_claimed_account_can_log_in_with_the_new_password(): void
    {
        $this->makeGuestUser();

        $this->postJson('/api/register', [
            'name' => 'Real Name',
            'email' => 'guest-buyer@example.com',
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ])->assertCreated();

        // Deliberately not calling /api/logout first: that route sits behind
        // `auth:sanctum` middleware, which pins the Auth facade's default
        // guard to 'sanctum' for the rest of the (shared, in-test) app
        // lifecycle — a RequestGuard with no attempt() method, which would
        // break the plain Auth::attempt() call in login() below. Hitting
        // /api/login directly (as a fresh, unauthenticated-guard request)
        // still proves the new password authenticates.
        $response = $this->postJson('/api/login', [
            'email' => 'guest-buyer@example.com',
            'password' => 'new-password123',
        ]);

        $response->assertOk()->assertJsonPath('data.email', 'guest-buyer@example.com');
    }

    public function test_a_user_can_register(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'New Customer',
            'email' => 'new@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated()->assertJsonPath('data.email', 'new@example.com');
        $this->assertDatabaseHas('users', ['email' => 'new@example.com', 'role' => 'customer']);
    }

    public function test_registration_requires_matching_password_confirmation(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'New Customer',
            'email' => 'new@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different',
        ]);

        $response->assertUnprocessable();
        $this->assertDatabaseMissing('users', ['email' => 'new@example.com']);
    }

    public function test_a_user_can_log_in_with_correct_credentials(): void
    {
        $user = User::factory()->create(['password' => 'correct-password']);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'correct-password',
        ]);

        $response->assertOk()->assertJsonPath('data.email', $user->email);
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_incorrect_password(): void
    {
        $user = User::factory()->create(['password' => 'correct-password']);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertUnprocessable();
        $this->assertGuest();
    }

    public function test_login_is_rate_limited_after_repeated_failures(): void
    {
        $user = User::factory()->create(['password' => 'correct-password']);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', ['email' => $user->email, 'password' => 'wrong'])
                ->assertUnprocessable();
        }

        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'wrong'])
            ->assertStatus(429);

        // A correct password is also blocked once the limit for this email+IP is hit.
        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'correct-password'])
            ->assertStatus(429);
    }

    public function test_a_logged_in_user_can_fetch_their_own_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/me');

        $response->assertOk()->assertJsonPath('data.id', $user->id);
    }

    public function test_guests_cannot_fetch_profile(): void
    {
        $this->getJson('/api/me')->assertUnauthorized();
    }

    public function test_a_user_can_log_out(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/logout');

        $response->assertNoContent();
    }
}
