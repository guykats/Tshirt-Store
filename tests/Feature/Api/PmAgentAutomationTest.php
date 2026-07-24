<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PmAgentAutomationTest extends TestCase
{
    use RefreshDatabase;

    protected function workflowUrl(string $suffix = ''): string
    {
        return "https://api.github.com/repos/guykats/Tshirt-Store/actions/workflows/pm-agent.yml{$suffix}";
    }

    public function test_customers_cannot_view_or_change_automation_state(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)->getJson('/api/pm-agent-automation')->assertForbidden();
        $this->actingAs($customer)->postJson('/api/pm-agent-automation/enable')->assertForbidden();
        $this->actingAs($customer)->postJson('/api/pm-agent-automation/disable')->assertForbidden();
    }

    public function test_guests_cannot_view_or_change_automation_state(): void
    {
        $this->getJson('/api/pm-agent-automation')->assertUnauthorized();
        $this->postJson('/api/pm-agent-automation/enable')->assertUnauthorized();
        $this->postJson('/api/pm-agent-automation/disable')->assertUnauthorized();
    }

    public function test_shows_not_configured_when_no_token_is_set(): void
    {
        config(['services.github_actions.token' => null]);
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->getJson('/api/pm-agent-automation');

        $response->assertOk()->assertJson(['configured' => false, 'enabled' => null]);
    }

    public function test_enabling_without_a_token_configured_returns_a_clear_error(): void
    {
        config(['services.github_actions.token' => null]);
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->postJson('/api/pm-agent-automation/enable');

        $response->assertStatus(503)->assertJsonStructure(['message']);
    }

    public function test_shows_current_workflow_state_when_configured(): void
    {
        config(['services.github_actions.token' => 'fake-token']);
        Http::fake([$this->workflowUrl() => Http::response(['state' => 'active'], 200)]);
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->getJson('/api/pm-agent-automation');

        $response->assertOk()->assertJson(['configured' => true, 'enabled' => true, 'state' => 'active']);
    }

    public function test_shows_disabled_state(): void
    {
        config(['services.github_actions.token' => 'fake-token']);
        Http::fake([$this->workflowUrl() => Http::response(['state' => 'disabled_manually'], 200)]);
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->getJson('/api/pm-agent-automation');

        $response->assertOk()->assertJson(['configured' => true, 'enabled' => false, 'state' => 'disabled_manually']);
    }

    public function test_an_admin_can_disable_the_automation_and_it_is_logged(): void
    {
        config(['services.github_actions.token' => 'fake-token']);
        Http::fake([$this->workflowUrl('/disable') => Http::response('', 204)]);
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Deciding Admin']);

        $response = $this->actingAs($admin)->postJson('/api/pm-agent-automation/disable');

        $response->assertOk()->assertJson(['configured' => true, 'enabled' => false]);
        Http::assertSent(fn ($request) => $request->url() === $this->workflowUrl('/disable') && $request->method() === 'PUT');
        $this->assertDatabaseHas('system_events', ['event_type' => 'pm_agent.disabled', 'actor_name' => 'Deciding Admin']);
    }

    public function test_an_admin_can_enable_the_automation_and_it_is_logged(): void
    {
        config(['services.github_actions.token' => 'fake-token']);
        Http::fake([$this->workflowUrl('/enable') => Http::response('', 204)]);
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Deciding Admin']);

        $response = $this->actingAs($admin)->postJson('/api/pm-agent-automation/enable');

        $response->assertOk()->assertJson(['configured' => true, 'enabled' => true]);
        Http::assertSent(fn ($request) => $request->url() === $this->workflowUrl('/enable') && $request->method() === 'PUT');
        $this->assertDatabaseHas('system_events', ['event_type' => 'pm_agent.enabled', 'actor_name' => 'Deciding Admin']);
    }

    public function test_an_upstream_github_error_returns_a_clean_error_response(): void
    {
        config(['services.github_actions.token' => 'fake-token']);
        Http::fake([$this->workflowUrl('/enable') => Http::response(['message' => 'Bad credentials'], 401)]);
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->postJson('/api/pm-agent-automation/enable');

        $response->assertStatus(502)->assertJsonStructure(['message']);
    }
}
