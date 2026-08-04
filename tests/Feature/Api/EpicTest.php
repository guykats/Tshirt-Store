<?php

namespace Tests\Feature\Api;

use App\Models\Epic;
use App\Models\ProjectTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EpicTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // A backfill migration seeds real Visioner Agent epics on every fresh
        // migration (including in tests via RefreshDatabase) — clear the slate.
        Epic::query()->delete();
    }

    protected function workflowUrl(string $suffix = ''): string
    {
        return "https://api.github.com/repos/guykats/Tshirt-Store/actions/workflows/pm-agent.yml{$suffix}";
    }

    public function test_customers_cannot_view_epics(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)->getJson('/api/epics')->assertForbidden();
    }

    public function test_guests_cannot_view_epics(): void
    {
        $this->getJson('/api/epics')->assertUnauthorized();
    }

    public function test_admins_can_view_proposed_epics_ordered_before_decided_ones(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Epic::create(['title' => 'Approved epic', 'status' => 'approved']);
        Epic::create(['title' => 'Proposed epic', 'status' => 'proposed']);

        $response = $this->actingAs($admin)->getJson('/api/epics');

        $response->assertOk()->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.title', 'Proposed epic')
            ->assertJsonPath('data.1.title', 'Approved epic');
    }

    public function test_an_epic_exposes_how_many_child_tasks_the_pm_has_created_for_it(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $epic = Epic::create(['title' => 'Custom Design Studio', 'status' => 'approved']);
        ProjectTask::create(['epic_id' => $epic->id, 'title' => 'Design picker UI', 'agent_name' => 'Dev Agent', 'status' => 'todo']);
        ProjectTask::create(['epic_id' => $epic->id, 'title' => 'Live preview', 'agent_name' => 'Dev Agent', 'status' => 'todo']);

        $response = $this->actingAs($admin)->getJson('/api/epics');

        $response->assertOk()->assertJsonPath('data.0.task_count', 2);
    }

    public function test_an_approved_epic_with_a_blocked_task_reads_build_status_blocked_even_if_other_tasks_are_done(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $epic = Epic::create(['title' => 'Custom Design Studio', 'status' => 'approved']);
        ProjectTask::create(['epic_id' => $epic->id, 'title' => 'Done task', 'agent_name' => 'Dev Agent', 'status' => 'done']);
        ProjectTask::create(['epic_id' => $epic->id, 'title' => 'Blocked task', 'agent_name' => 'Dev Agent', 'status' => 'blocked']);
        ProjectTask::create(['epic_id' => $epic->id, 'title' => 'In progress task', 'agent_name' => 'Dev Agent', 'status' => 'in_progress']);

        $response = $this->actingAs($admin)->getJson('/api/epics');

        $response->assertOk()->assertJsonPath('data.0.build_status', 'blocked');
    }

    public function test_an_approved_epic_with_all_done_tasks_reads_build_status_done(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $epic = Epic::create(['title' => 'Custom Design Studio', 'status' => 'approved']);
        ProjectTask::create(['epic_id' => $epic->id, 'title' => 'Design picker UI', 'agent_name' => 'Dev Agent', 'status' => 'done']);
        ProjectTask::create(['epic_id' => $epic->id, 'title' => 'Live preview', 'agent_name' => 'Dev Agent', 'status' => 'done']);

        $response = $this->actingAs($admin)->getJson('/api/epics');

        $response->assertOk()->assertJsonPath('data.0.build_status', 'done');
    }

    public function test_an_approved_epic_with_zero_linked_tasks_reads_build_status_todo(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Epic::create(['title' => 'Custom Design Studio', 'status' => 'approved']);

        $response = $this->actingAs($admin)->getJson('/api/epics');

        $response->assertOk()->assertJsonPath('data.0.build_status', 'todo');
    }

    public function test_a_proposed_or_rejected_epics_build_status_is_null_regardless_of_linked_tasks(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $proposed = Epic::create(['title' => 'Proposed epic', 'status' => 'proposed']);
        $rejected = Epic::create(['title' => 'Rejected epic', 'status' => 'rejected']);
        ProjectTask::create(['epic_id' => $proposed->id, 'title' => 'Some task', 'agent_name' => 'Dev Agent', 'status' => 'done']);
        ProjectTask::create(['epic_id' => $rejected->id, 'title' => 'Another task', 'agent_name' => 'Dev Agent', 'status' => 'blocked']);

        $response = $this->actingAs($admin)->getJson('/api/epics');

        $response->assertOk()
            ->assertJsonPath('data.0.build_status', null)
            ->assertJsonPath('data.1.build_status', null);
    }

    public function test_build_status_query_param_filters_to_only_matching_epics(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $blockedEpic = Epic::create(['title' => 'Blocked epic', 'status' => 'approved']);
        ProjectTask::create(['epic_id' => $blockedEpic->id, 'title' => 'Blocked task', 'agent_name' => 'Dev Agent', 'status' => 'blocked']);
        $doneEpic = Epic::create(['title' => 'Done epic', 'status' => 'approved']);
        ProjectTask::create(['epic_id' => $doneEpic->id, 'title' => 'Finished task', 'agent_name' => 'Dev Agent', 'status' => 'done']);

        $response = $this->actingAs($admin)->getJson('/api/epics?status=approved&build_status=blocked');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Blocked epic')
            ->assertJsonPath('data.0.build_status', 'blocked');
    }

    public function test_customers_cannot_decide_an_epic(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $epic = Epic::create(['title' => 'Proposed epic', 'status' => 'proposed']);

        $this->actingAs($customer)->postJson("/api/epics/{$epic->id}/approve")->assertForbidden();
        $this->assertDatabaseHas('epics', ['id' => $epic->id, 'status' => 'proposed']);
    }

    public function test_an_admin_can_approve_an_epic_and_it_is_logged(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Deciding Admin']);
        $epic = Epic::create(['title' => 'Custom Design Studio', 'status' => 'proposed']);

        $response = $this->actingAs($admin)->postJson("/api/epics/{$epic->id}/approve");

        $response->assertOk()->assertJsonPath('data.status', 'approved');
        $this->assertDatabaseHas('epics', ['id' => $epic->id, 'status' => 'approved', 'decided_by' => $admin->id]);
        $this->assertDatabaseHas('system_events', ['event_type' => 'epic.approved', 'actor_name' => 'Deciding Admin']);
    }

    public function test_approving_an_epic_re_enables_a_disabled_pm_agent_workflow(): void
    {
        // Real bug: a disabled workflow can never fire on its own schedule
        // to notice new approved work exists - run #157 -> #158 sat idle for
        // almost 21 hours because nothing woke it back up after an epic got
        // approved while it happened to be off. Approving must do it itself.
        config(['services.github_actions.token' => 'fake-token']);
        Http::fake([
            $this->workflowUrl() => Http::response(['state' => 'disabled_manually'], 200),
            $this->workflowUrl('/enable') => Http::response('', 204),
        ]);
        $admin = User::factory()->create(['role' => 'admin']);
        $epic = Epic::create(['title' => 'Custom Design Studio', 'status' => 'proposed']);

        $this->actingAs($admin)->postJson("/api/epics/{$epic->id}/approve")->assertOk();

        Http::assertSent(fn ($request) => $request->url() === $this->workflowUrl('/enable') && $request->method() === 'PUT');
        $this->assertDatabaseHas('system_events', ['event_type' => 'pm_agent.auto_enabled']);
    }

    public function test_approving_an_epic_leaves_an_already_enabled_workflow_alone(): void
    {
        config(['services.github_actions.token' => 'fake-token']);
        Http::fake([$this->workflowUrl() => Http::response(['state' => 'active'], 200)]);
        $admin = User::factory()->create(['role' => 'admin']);
        $epic = Epic::create(['title' => 'Custom Design Studio', 'status' => 'proposed']);

        $this->actingAs($admin)->postJson("/api/epics/{$epic->id}/approve")->assertOk();

        Http::assertNotSent(fn ($request) => $request->method() === 'PUT');
        $this->assertDatabaseMissing('system_events', ['event_type' => 'pm_agent.auto_enabled']);
    }

    public function test_an_admin_can_reject_an_epic(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $epic = Epic::create(['title' => 'Wholesale channel', 'status' => 'proposed']);

        $response = $this->actingAs($admin)->postJson("/api/epics/{$epic->id}/reject");

        $response->assertOk()->assertJsonPath('data.status', 'rejected');
        $this->assertDatabaseHas('epics', ['id' => $epic->id, 'status' => 'rejected']);
    }

    public function test_delaying_an_epic_sends_it_to_the_back_of_the_proposed_list(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $first = Epic::create(['title' => 'First epic', 'status' => 'proposed', 'priority' => 0]);
        $second = Epic::create(['title' => 'Second epic', 'status' => 'proposed', 'priority' => 0]);

        $this->actingAs($admin)->postJson("/api/epics/{$first->id}/delay")->assertOk();

        $response = $this->actingAs($admin)->getJson('/api/epics');

        $response->assertOk()
            ->assertJsonPath('data.0.title', 'Second epic')
            ->assertJsonPath('data.1.title', 'First epic');
        $this->assertDatabaseHas('epics', ['id' => $first->id, 'status' => 'proposed']);
        $this->assertTrue($first->fresh()->priority > $second->fresh()->priority);
    }
}
