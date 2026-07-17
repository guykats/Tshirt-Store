<?php

namespace Tests\Feature\Api;

use App\Models\AgentStatus;
use App\Models\ProjectTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The historical backfill migrations seed real project_tasks rows on every
        // fresh migration (including in tests), so start from a clean slate rather
        // than assuming an empty table.
        ProjectTask::query()->delete();
    }

    public function test_current_task_is_derived_from_the_agents_in_progress_project_task(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $agent = AgentStatus::create(['agent_name' => 'Dev Agent', 'status' => 'idle']);

        ProjectTask::create(['title' => 'Old stale task', 'agent_name' => 'Dev Agent', 'status' => 'done']);
        ProjectTask::create(['title' => 'Live task in flight', 'agent_name' => 'Dev Agent', 'status' => 'in_progress']);

        $response = $this->actingAs($admin)->getJson('/api/agent-statuses');

        $row = collect($response->json('data'))->firstWhere('agent_name', 'Dev Agent');
        $this->assertSame('Live task in flight', $row['current_task']);
        $this->assertSame('in_progress', $row['current_task_status']);
        $this->assertSame($agent->id, $row['id']);
    }

    public function test_current_task_falls_back_to_most_recent_done_task_when_nothing_is_in_progress(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        AgentStatus::create(['agent_name' => 'Ops Agent', 'status' => 'idle']);

        ProjectTask::create(['title' => 'Finished task', 'agent_name' => 'Ops Agent', 'status' => 'done']);

        $response = $this->actingAs($admin)->getJson('/api/agent-statuses');

        $row = collect($response->json('data'))->firstWhere('agent_name', 'Ops Agent');
        $this->assertSame('Finished task', $row['current_task']);
    }

    public function test_backlog_count_reflects_todo_tasks_for_that_agent(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        AgentStatus::create(['agent_name' => 'Creative Agent', 'status' => 'idle']);

        ProjectTask::create(['title' => 'Todo one', 'agent_name' => 'Creative Agent', 'status' => 'todo']);
        ProjectTask::create(['title' => 'Todo two', 'agent_name' => 'Creative Agent', 'status' => 'todo']);

        $response = $this->actingAs($admin)->getJson('/api/agent-statuses');

        $row = collect($response->json('data'))->firstWhere('agent_name', 'Creative Agent');
        $this->assertSame(2, $row['backlog_count']);
    }

    public function test_updating_agent_status_no_longer_accepts_a_manual_current_task(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $agent = AgentStatus::create(['agent_name' => 'Trend Agent', 'status' => 'idle']);

        $response = $this->actingAs($admin)->patchJson("/api/agent-statuses/{$agent->id}", [
            'status' => 'executing',
            'current_task' => 'This should be ignored',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('agent_statuses', ['id' => $agent->id, 'status' => 'executing']);
        $this->assertDatabaseMissing('agent_statuses', ['id' => $agent->id, 'current_task' => 'This should be ignored']);
    }
}
