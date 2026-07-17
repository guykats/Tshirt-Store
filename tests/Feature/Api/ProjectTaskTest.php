<?php

namespace Tests\Feature\Api;

use App\Models\ProjectTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTaskTest extends TestCase
{
    use RefreshDatabase;

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
}
