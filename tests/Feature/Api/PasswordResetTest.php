<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_requesting_a_reset_for_a_valid_email_sends_a_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $response = $this->postJson('/api/forgot-password', ['email' => $user->email]);

        $response->assertOk();
        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_requesting_a_reset_for_a_nonexistent_email_still_returns_generic_success(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/forgot-password', ['email' => 'nobody@example.com']);

        $response->assertOk();
        Notification::assertNothingSent();
    }

    public function test_resetting_with_a_valid_token_changes_the_password_and_allows_login(): void
    {
        $user = User::factory()->create(['password' => 'old-password123']);

        $token = Password::createToken($user);

        $response = $this->postJson('/api/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $response->assertOk();

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'new-password123',
        ])->assertOk();
    }

    public function test_resetting_with_an_invalid_token_fails_cleanly(): void
    {
        $user = User::factory()->create(['password' => 'old-password123']);

        $response = $this->postJson('/api/reset-password', [
            'token' => 'not-a-real-token',
            'email' => $user->email,
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $response->assertUnprocessable();

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'old-password123',
        ])->assertOk();
    }

    public function test_forgot_password_is_rate_limited_after_repeated_requests(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/forgot-password', ['email' => $user->email])->assertOk();
        }

        $this->postJson('/api/forgot-password', ['email' => $user->email])
            ->assertStatus(429);
    }
}
