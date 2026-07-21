<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AccountSecurityRateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The 'account-security' limiter is keyed by user id; clear it between
        // tests so one test's hits don't bleed into the next (user ids can
        // collide across tests since each test starts a fresh migrated database).
        RateLimiter::clear('account-security:1');
    }

    public function test_a_user_retrying_a_rejected_password_change_a_few_times_is_never_throttled(): void
    {
        $user = User::factory()->create(['password' => 'correct-password123']);

        for ($i = 0; $i < 4; $i++) {
            $this->actingAs($user)->postJson('/api/change-password', [
                'current_password' => 'wrong-password',
                'password' => 'new-password456',
                'password_confirmation' => 'new-password456',
            ])->assertUnprocessable();
        }
    }

    public function test_hammering_change_password_past_the_per_user_limit_returns_429(): void
    {
        $user = User::factory()->create(['password' => 'correct-password123']);

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($user)->postJson('/api/change-password', [
                'current_password' => 'wrong-password',
                'password' => 'new-password456',
                'password_confirmation' => 'new-password456',
            ]);
        }

        // The 6th request within the same minute, from the same user, exceeds
        // the 'account-security' limiter and must be rejected before the
        // controller's Hash::check ever runs, since that check is exactly what
        // makes this endpoint a password-guessing oracle if left unthrottled.
        $this->actingAs($user)->postJson('/api/change-password', [
            'current_password' => 'wrong-password',
            'password' => 'new-password456',
            'password_confirmation' => 'new-password456',
        ])->assertStatus(429);
    }

    public function test_the_rate_limit_is_shared_with_account_deletion_for_the_same_user(): void
    {
        $user = User::factory()->create(['password' => 'correct-password123']);

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($user)->postJson('/api/change-password', [
                'current_password' => 'wrong-password',
                'password' => 'new-password456',
                'password_confirmation' => 'new-password456',
            ]);
        }

        $this->actingAs($user)->deleteJson('/api/account', [
            'current_password' => 'correct-password123',
        ])->assertStatus(429);

        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('correct-password123', $user->fresh()->password));
    }

    public function test_different_users_have_independent_rate_limit_buckets(): void
    {
        // Deliberately left on the factory's default (shared-hash) password rather
        // than an explicit override: actingAs() swapping the acting user mid-test
        // doesn't go through a real login(), so Sanctum's AuthenticateSession
        // middleware (wired in via EnsureFrontendRequestsAreStateful for these
        // "frontend" requests) would otherwise see the session's stored password
        // hash from userA's requests stop matching userB's and invalidate the
        // session - a test-harness artifact, not a real cross-user auth bug, that
        // only surfaces when the two users' hashes actually differ.
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($userA)->postJson('/api/change-password', [
                'current_password' => 'wrong-password',
                'password' => 'new-password456',
                'password_confirmation' => 'new-password456',
            ]);
        }
        $this->actingAs($userA)->postJson('/api/change-password', [
            'current_password' => 'wrong-password',
            'password' => 'new-password456',
            'password_confirmation' => 'new-password456',
        ])->assertStatus(429);

        // A different user is unaffected by userA's exhausted bucket.
        $this->actingAs($userB)->postJson('/api/change-password', [
            'current_password' => 'password',
            'password' => 'new-password456',
            'password_confirmation' => 'new-password456',
        ])->assertOk();
    }

    public function test_guests_hammering_the_endpoint_are_rejected_by_auth_before_the_limiter_matters(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->postJson('/api/change-password', [
                'current_password' => 'whatever',
                'password' => 'new-password456',
                'password_confirmation' => 'new-password456',
            ])->assertUnauthorized();
        }
    }
}
