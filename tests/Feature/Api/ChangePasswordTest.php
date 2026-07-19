<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ChangePasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_logged_in_user_can_change_their_password(): void
    {
        $user = User::factory()->create(['password' => 'old-password123']);

        $response = $this->actingAs($user)->postJson('/api/change-password', [
            'current_password' => 'old-password123',
            'password' => 'new-password456',
            'password_confirmation' => 'new-password456',
        ]);

        $response->assertOk();
        $this->assertTrue(Hash::check('new-password456', $user->fresh()->password));
        $this->assertFalse(Hash::check('old-password123', $user->fresh()->password));
    }

    public function test_changing_password_requires_the_correct_current_password(): void
    {
        $user = User::factory()->create(['password' => 'old-password123']);

        $response = $this->actingAs($user)->postJson('/api/change-password', [
            'current_password' => 'wrong-password',
            'password' => 'new-password456',
            'password_confirmation' => 'new-password456',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('current_password');
        $this->assertTrue(Hash::check('old-password123', $user->fresh()->password));
    }

    public function test_changing_password_rejects_a_weak_new_password(): void
    {
        $user = User::factory()->create(['password' => 'old-password123']);

        $response = $this->actingAs($user)->postJson('/api/change-password', [
            'current_password' => 'old-password123',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('password');
        $this->assertTrue(Hash::check('old-password123', $user->fresh()->password));
    }

    public function test_changing_password_rejects_a_mismatched_confirmation(): void
    {
        $user = User::factory()->create(['password' => 'old-password123']);

        $response = $this->actingAs($user)->postJson('/api/change-password', [
            'current_password' => 'old-password123',
            'password' => 'new-password456',
            'password_confirmation' => 'does-not-match',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('password');
        $this->assertTrue(Hash::check('old-password123', $user->fresh()->password));
    }

    public function test_guests_cannot_change_a_password(): void
    {
        $this->postJson('/api/change-password', [
            'current_password' => 'whatever',
            'password' => 'new-password456',
            'password_confirmation' => 'new-password456',
        ])->assertUnauthorized();
    }
}
