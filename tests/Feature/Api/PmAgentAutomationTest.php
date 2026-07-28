<?php

namespace Tests\Feature\Api;

use App\Models\Epic;
use App\Models\ProjectTask;
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

    public function test_disable_if_idle_rejects_requests_without_the_shared_token(): void
    {
        config(['services.pm_agent.board_token' => 'board-secret']);

        $this->postJson('/api/pm-agent-automation/disable-if-idle')->assertForbidden();
        $this->postJson('/api/pm-agent-automation/disable-if-idle', [], ['X-PM-Agent-Token' => 'wrong'])->assertForbidden();
    }

    public function test_disable_if_idle_returns_not_configured_without_a_board_token(): void
    {
        config(['services.pm_agent.board_token' => null]);

        $this->postJson('/api/pm-agent-automation/disable-if-idle', [], ['X-PM-Agent-Token' => 'anything'])
            ->assertStatus(503);
    }

    public function test_disable_if_idle_leaves_automation_alone_when_approved_work_exists(): void
    {
        config(['services.pm_agent.board_token' => 'board-secret']);
        ProjectTask::create(['title' => 'Approved task', 'agent_name' => 'Dev Agent', 'status' => 'todo', 'approved_for_dev' => true]);

        $response = $this->postJson('/api/pm-agent-automation/disable-if-idle', [], ['X-PM-Agent-Token' => 'board-secret']);

        $response->assertOk()->assertJson(['disabled' => false, 'reason' => 'approved_work_exists']);
        Http::assertNothingSent();
    }

    public function test_disable_if_idle_leaves_automation_alone_when_already_disabled(): void
    {
        // Migration-seeded approved todo tasks (e.g. real epic breakdowns)
        // would otherwise short-circuit this on the approved_work_exists
        // branch before ever reaching the github-state check under test.
        ProjectTask::query()->delete();
        config(['services.pm_agent.board_token' => 'board-secret', 'services.github_actions.token' => 'fake-token']);
        Http::fake([$this->workflowUrl() => Http::response(['state' => 'disabled_manually'], 200)]);

        $response = $this->postJson('/api/pm-agent-automation/disable-if-idle', [], ['X-PM-Agent-Token' => 'board-secret']);

        $response->assertOk()->assertJson(['disabled' => false, 'reason' => 'already_disabled']);
        Http::assertNotSent(fn ($request) => $request->method() === 'PUT');
    }

    public function test_disable_if_idle_disables_and_logs_when_nothing_is_approved(): void
    {
        // See the comment in the "already disabled" test above — this test's
        // whole premise is that no approved todo task exists.
        ProjectTask::query()->delete();
        config(['services.pm_agent.board_token' => 'board-secret', 'services.github_actions.token' => 'fake-token']);
        Http::fake([
            $this->workflowUrl() => Http::response(['state' => 'active'], 200),
            $this->workflowUrl('/disable') => Http::response('', 204),
        ]);
        ProjectTask::create(['title' => 'Unapproved task', 'agent_name' => 'Dev Agent', 'status' => 'todo', 'approved_for_dev' => false]);
        ProjectTask::create(['title' => 'Done task', 'agent_name' => 'Dev Agent', 'status' => 'done', 'approved_for_dev' => true]);

        $response = $this->postJson('/api/pm-agent-automation/disable-if-idle', [], ['X-PM-Agent-Token' => 'board-secret']);

        $response->assertOk()->assertJson(['disabled' => true, 'reason' => 'idle']);
        Http::assertSent(fn ($request) => $request->url() === $this->workflowUrl('/disable') && $request->method() === 'PUT');
        $this->assertDatabaseHas('system_events', ['event_type' => 'pm_agent.auto_disabled']);
    }

    public function test_disable_if_idle_returns_a_clean_error_when_github_state_check_fails(): void
    {
        // See the comment in the "already disabled" test above — this test's
        // whole premise is that no approved todo task exists, so the code
        // actually reaches the github-state check being tested.
        ProjectTask::query()->delete();
        config(['services.pm_agent.board_token' => 'board-secret', 'services.github_actions.token' => 'fake-token']);
        Http::fake([$this->workflowUrl() => Http::response(['message' => 'Bad credentials'], 401)]);

        $response = $this->postJson('/api/pm-agent-automation/disable-if-idle', [], ['X-PM-Agent-Token' => 'board-secret']);

        $response->assertStatus(502)->assertJsonStructure(['message']);
    }

    public function test_approved_todo_titles_rejects_requests_without_the_shared_token(): void
    {
        config(['services.pm_agent.board_token' => 'board-secret']);

        $this->getJson('/api/pm-agent-automation/approved-todo-titles')->assertForbidden();
        $this->get('/api/pm-agent-automation/approved-todo-titles', ['X-PM-Agent-Token' => 'wrong'])->assertForbidden();
    }

    public function test_approved_todo_titles_returns_not_configured_without_a_board_token(): void
    {
        config(['services.pm_agent.board_token' => null]);

        $this->get('/api/pm-agent-automation/approved-todo-titles', ['X-PM-Agent-Token' => 'anything'])
            ->assertStatus(503);
    }

    public function test_approved_todo_titles_exports_exactly_the_approved_todo_set(): void
    {
        // Asserts an exact title set, so migration-seeded approved todo
        // tasks (e.g. real epic breakdowns) must be cleared first — see
        // CLAUDE.md's RefreshDatabase/exact-row-count gotcha.
        ProjectTask::query()->delete();
        config(['services.pm_agent.board_token' => 'board-secret']);
        ProjectTask::create(['title' => 'Approved todo', 'agent_name' => 'Dev Agent', 'status' => 'todo', 'approved_for_dev' => true]);
        ProjectTask::create(['title' => 'Unapproved todo', 'agent_name' => 'Dev Agent', 'status' => 'todo', 'approved_for_dev' => false]);
        ProjectTask::create(['title' => 'Approved but done', 'agent_name' => 'Dev Agent', 'status' => 'done', 'approved_for_dev' => true]);

        $response = $this->get('/api/pm-agent-automation/approved-todo-titles', ['X-PM-Agent-Token' => 'board-secret']);

        $response->assertOk()->assertJson(['configured' => true, 'titles' => ['Approved todo']]);
    }

    public function test_epic_decisions_rejects_requests_without_the_shared_token(): void
    {
        config(['services.pm_agent.board_token' => 'board-secret']);

        $this->getJson('/api/pm-agent-automation/epic-decisions')->assertForbidden();
        $this->get('/api/pm-agent-automation/epic-decisions', ['X-PM-Agent-Token' => 'wrong'])->assertForbidden();
    }

    public function test_epic_decisions_returns_not_configured_without_a_board_token(): void
    {
        config(['services.pm_agent.board_token' => null]);

        $this->get('/api/pm-agent-automation/epic-decisions', ['X-PM-Agent-Token' => 'anything'])
            ->assertStatus(503);
    }

    public function test_epic_decisions_exports_the_real_epics_table(): void
    {
        Epic::query()->delete();
        config(['services.pm_agent.board_token' => 'board-secret']);
        Epic::create(['title' => 'Approved live', 'description' => 'Decided on the live dashboard', 'agent_name' => 'Visioner Agent', 'status' => 'approved', 'priority' => 0]);

        $response = $this->get('/api/pm-agent-automation/epic-decisions', ['X-PM-Agent-Token' => 'board-secret']);

        $response->assertOk()
            ->assertJsonPath('configured', true)
            ->assertJsonPath('epics.0.title', 'Approved live')
            ->assertJsonPath('epics.0.status', 'approved');
    }
}
