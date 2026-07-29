<?php

namespace Tests\Feature\Api;

use App\Models\Epic;
use App\Models\ProjectTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProjectTaskTest extends TestCase
{
    use RefreshDatabase;

    protected function workflowUrl(string $suffix = ''): string
    {
        return "https://api.github.com/repos/guykats/Tshirt-Store/actions/workflows/pm-agent.yml{$suffix}";
    }

    public function test_customers_cannot_view_project_tasks(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        ProjectTask::create(['title' => 'Task', 'agent_name' => 'Dev Agent', 'status' => 'done']);

        $this->actingAs($customer)->getJson('/api/project-tasks')->assertForbidden();
    }

    public function test_guests_cannot_view_project_tasks(): void
    {
        $this->getJson('/api/project-tasks')->assertUnauthorized();
    }

    public function test_admins_can_view_project_tasks_with_counts(): void
    {
        // The historical backfill migration seeds real rows on every fresh migration
        // (including in tests, via RefreshDatabase), so start from a clean slate here
        // rather than assuming an empty table.
        ProjectTask::query()->delete();

        $admin = User::factory()->create(['role' => 'admin']);
        ProjectTask::create(['title' => 'Done task', 'agent_name' => 'Dev Agent', 'status' => 'done']);
        ProjectTask::create(['title' => 'Blocked task', 'agent_name' => 'Ops Agent', 'status' => 'blocked', 'blocked_reason' => 'Waiting on credentials']);

        $response = $this->actingAs($admin)->getJson('/api/project-tasks');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('counts.done', 1)
            ->assertJsonPath('counts.blocked', 1);
    }

    public function test_project_tasks_can_be_filtered_by_status_and_agent(): void
    {
        ProjectTask::query()->delete();

        $admin = User::factory()->create(['role' => 'admin']);
        ProjectTask::create(['title' => 'Done Dev task', 'agent_name' => 'Dev Agent', 'status' => 'done']);
        ProjectTask::create(['title' => 'Todo Ops task', 'agent_name' => 'Ops Agent', 'status' => 'todo']);

        $this->actingAs($admin)->getJson('/api/project-tasks?status=todo')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', 'Todo Ops task');

        $this->actingAs($admin)->getJson('/api/project-tasks?agent=Dev Agent')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', 'Done Dev task');
    }

    public function test_project_tasks_can_be_filtered_by_the_approved_pseudo_status(): void
    {
        // "approved" isn't a real status column value - it's a filter-only
        // alias for todo tasks with approved_for_dev=1, the same set the PM
        // Agent itself builds from. In_progress tasks that happen to still
        // carry approved_for_dev=1 from before they were picked up must NOT
        // match, since they're no longer todo.
        ProjectTask::query()->delete();

        $admin = User::factory()->create(['role' => 'admin']);
        ProjectTask::create(['title' => 'Approved todo task', 'agent_name' => 'Dev Agent', 'status' => 'todo', 'approved_for_dev' => true]);
        ProjectTask::create(['title' => 'Unapproved todo task', 'agent_name' => 'Dev Agent', 'status' => 'todo', 'approved_for_dev' => false]);
        ProjectTask::create(['title' => 'In progress task', 'agent_name' => 'Dev Agent', 'status' => 'in_progress', 'approved_for_dev' => true]);

        $response = $this->actingAs($admin)->getJson('/api/project-tasks?status=approved');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Approved todo task')
            ->assertJsonPath('counts.approved', 1);
    }

    public function test_a_task_linked_to_an_epic_exposes_the_epic_title(): void
    {
        // ProjectTaskController::index must eager-load the epic relation -
        // ProjectTaskResource's epic_title uses whenLoaded, so without it
        // this silently stayed null for every task regardless of epic_id.
        ProjectTask::query()->delete();
        Epic::query()->delete();

        $admin = User::factory()->create(['role' => 'admin']);
        $epic = Epic::create(['title' => 'Loyalty Program', 'agent_name' => 'Visioner Agent', 'status' => 'approved']);
        ProjectTask::create(['epic_id' => $epic->id, 'title' => 'Epic-derived task', 'agent_name' => 'Dev Agent', 'status' => 'todo']);

        $response = $this->actingAs($admin)->getJson('/api/project-tasks');

        $response->assertOk()
            ->assertJsonPath('data.0.epic_id', $epic->id)
            ->assertJsonPath('data.0.epic_title', 'Loyalty Program');
    }

    public function test_a_task_exposes_requested_by_when_set(): void
    {
        ProjectTask::query()->delete();

        $admin = User::factory()->create(['role' => 'admin']);
        ProjectTask::create(['title' => 'Ad hoc task from chat', 'agent_name' => 'Dev Agent', 'status' => 'done', 'requested_by' => 'Guy']);

        $response = $this->actingAs($admin)->getJson('/api/project-tasks');

        $response->assertOk()->assertJsonPath('data.0.requested_by', 'Guy');
    }

    public function test_a_task_with_a_screenshot_exposes_a_screenshot_url(): void
    {
        ProjectTask::query()->delete();

        $admin = User::factory()->create(['role' => 'admin']);
        ProjectTask::create([
            'title' => 'UI task', 'agent_name' => 'Creative Agent', 'status' => 'done',
            'screenshot_path' => 'task-screenshots/example.png',
        ]);

        $response = $this->actingAs($admin)->getJson('/api/project-tasks');

        $response->assertOk();
        $this->assertStringContainsString('task-screenshots/example.png', $response->json('data.0.screenshot_url'));
    }

    public function test_todo_tasks_default_to_not_approved_for_dev(): void
    {
        ProjectTask::query()->delete();

        $admin = User::factory()->create(['role' => 'admin']);
        ProjectTask::create(['title' => 'Fresh task', 'agent_name' => 'Dev Agent', 'status' => 'todo']);

        $response = $this->actingAs($admin)->getJson('/api/project-tasks');

        $response->assertOk()->assertJsonPath('data.0.approved_for_dev', false);
    }

    public function test_admins_can_approve_a_task_for_development(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $task = ProjectTask::create(['title' => 'Approve me', 'agent_name' => 'Dev Agent', 'status' => 'todo']);

        $response = $this->actingAs($admin)->postJson("/api/project-tasks/{$task->id}/approve");

        $response->assertOk()->assertJsonPath('data.approved_for_dev', true);
        $this->assertTrue($task->fresh()->approved_for_dev);
    }

    public function test_approving_a_task_re_enables_a_disabled_pm_agent_workflow(): void
    {
        // Same real bug as the epic-approval equivalent: a disabled workflow
        // can never fire on its own schedule to notice new approved work
        // exists, so approving must wake it back up itself rather than
        // waiting for a cron cycle that will never come.
        config(['services.github_actions.token' => 'fake-token']);
        Http::fake([
            $this->workflowUrl() => Http::response(['state' => 'disabled_manually'], 200),
            $this->workflowUrl('/enable') => Http::response('', 204),
        ]);
        $admin = User::factory()->create(['role' => 'admin']);
        $task = ProjectTask::create(['title' => 'Approve me', 'agent_name' => 'Dev Agent', 'status' => 'todo']);

        $this->actingAs($admin)->postJson("/api/project-tasks/{$task->id}/approve")->assertOk();

        Http::assertSent(fn ($request) => $request->url() === $this->workflowUrl('/enable') && $request->method() === 'PUT');
        $this->assertDatabaseHas('system_events', ['event_type' => 'pm_agent.auto_enabled']);
    }

    public function test_approving_a_task_leaves_an_already_enabled_workflow_alone(): void
    {
        config(['services.github_actions.token' => 'fake-token']);
        Http::fake([$this->workflowUrl() => Http::response(['state' => 'active'], 200)]);
        $admin = User::factory()->create(['role' => 'admin']);
        $task = ProjectTask::create(['title' => 'Approve me', 'agent_name' => 'Dev Agent', 'status' => 'todo']);

        $this->actingAs($admin)->postJson("/api/project-tasks/{$task->id}/approve")->assertOk();

        Http::assertNotSent(fn ($request) => $request->method() === 'PUT');
        $this->assertDatabaseMissing('system_events', ['event_type' => 'pm_agent.auto_enabled']);
    }

    public function test_admins_can_revoke_approval(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $task = ProjectTask::create(['title' => 'Revoke me', 'agent_name' => 'Dev Agent', 'status' => 'todo', 'approved_for_dev' => true]);

        $response = $this->actingAs($admin)->postJson("/api/project-tasks/{$task->id}/unapprove");

        $response->assertOk()->assertJsonPath('data.approved_for_dev', false);
        $this->assertFalse($task->fresh()->approved_for_dev);
    }

    public function test_customers_cannot_approve_a_task(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $task = ProjectTask::create(['title' => 'Not for you', 'agent_name' => 'Dev Agent', 'status' => 'todo']);

        $this->actingAs($customer)->postJson("/api/project-tasks/{$task->id}/approve")->assertForbidden();
        $this->assertFalse($task->fresh()->approved_for_dev);
    }

    public function test_guests_cannot_approve_a_task(): void
    {
        $task = ProjectTask::create(['title' => 'Not for you either', 'agent_name' => 'Dev Agent', 'status' => 'todo']);

        $this->postJson("/api/project-tasks/{$task->id}/approve")->assertUnauthorized();
    }
}
