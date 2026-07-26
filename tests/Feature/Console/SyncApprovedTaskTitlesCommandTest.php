<?php

namespace Tests\Feature\Console;

use App\Models\ProjectTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Runs inside pm-agent.yml right after migrate:fresh --seed, before the PM
 * Agent starts deciding what to build — see App\Console\Commands\SyncApprovedTaskTitles.
 * Its whole job is to overwrite this CI run's freshly-replayed
 * project_tasks.approved_for_dev flags with the real, live values fetched
 * from production, since the replay only ever reflects migration history.
 */
class SyncApprovedTaskTitlesCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function endpointUrl(): string
    {
        return 'https://store.guykats.com/api/pm-agent-automation/approved-todo-titles';
    }

    public function test_leaves_approvals_untouched_when_no_board_token_is_set(): void
    {
        putenv('PM_AGENT_BOARD_TOKEN');
        ProjectTask::create(['title' => 'Migration-approved task', 'agent_name' => 'Dev Agent', 'status' => 'todo', 'approved_for_dev' => true]);

        $this->artisan('pm-agent:sync-approved-titles')->assertExitCode(0);

        $this->assertTrue(ProjectTask::where('title', 'Migration-approved task')->value('approved_for_dev'));
        Http::assertNothingSent();
    }

    public function test_leaves_approvals_untouched_when_production_is_unreachable(): void
    {
        putenv('PM_AGENT_BOARD_TOKEN=board-secret');
        Http::fake([$this->endpointUrl() => Http::response(['message' => 'Server error'], 500)]);
        ProjectTask::create(['title' => 'Migration-approved task', 'agent_name' => 'Dev Agent', 'status' => 'todo', 'approved_for_dev' => true]);

        $this->artisan('pm-agent:sync-approved-titles')->assertExitCode(0);

        $this->assertTrue(ProjectTask::where('title', 'Migration-approved task')->value('approved_for_dev'));

        putenv('PM_AGENT_BOARD_TOKEN');
    }

    public function test_leaves_approvals_untouched_when_production_is_unreachable_over_the_network(): void
    {
        // Distinct from the 500-response case above: a run (#132) actually
        // crashed the whole pm-agent.yml job over exactly this - a
        // ConnectionException (DNS/timeout/network failure) is a different
        // exception type than RequestException (a real HTTP 4xx/5xx) and
        // must be caught too, or php artisan exits non-zero and the CI step
        // aborts the run before the PM Agent ever starts.
        putenv('PM_AGENT_BOARD_TOKEN=board-secret');
        Http::fake([$this->endpointUrl() => function () {
            throw new ConnectionException('cURL error 28: Failed to connect to store.guykats.com port 443 after 10001 ms: Timeout was reached');
        }]);
        ProjectTask::create(['title' => 'Migration-approved task', 'agent_name' => 'Dev Agent', 'status' => 'todo', 'approved_for_dev' => true]);

        $this->artisan('pm-agent:sync-approved-titles')->assertExitCode(0);

        $this->assertTrue(ProjectTask::where('title', 'Migration-approved task')->value('approved_for_dev'));

        putenv('PM_AGENT_BOARD_TOKEN');
    }

    public function test_revokes_a_migration_only_approval_the_live_titles_no_longer_include(): void
    {
        putenv('PM_AGENT_BOARD_TOKEN=board-secret');
        Http::fake([$this->endpointUrl() => Http::response(['configured' => true, 'titles' => []])]);
        ProjectTask::create(['title' => 'Migration-approved but not live', 'agent_name' => 'Dev Agent', 'status' => 'todo', 'approved_for_dev' => true]);

        $this->artisan('pm-agent:sync-approved-titles')->assertExitCode(0);

        $this->assertFalse(ProjectTask::where('title', 'Migration-approved but not live')->value('approved_for_dev'));

        putenv('PM_AGENT_BOARD_TOKEN');
    }

    public function test_grants_a_live_approval_the_migration_history_never_recorded(): void
    {
        putenv('PM_AGENT_BOARD_TOKEN=board-secret');
        Http::fake([$this->endpointUrl() => Http::response(['configured' => true, 'titles' => ['Approved live only']])]);
        ProjectTask::create(['title' => 'Approved live only', 'agent_name' => 'Dev Agent', 'status' => 'todo', 'approved_for_dev' => false]);
        ProjectTask::create(['title' => 'Not approved anywhere', 'agent_name' => 'Dev Agent', 'status' => 'todo', 'approved_for_dev' => false]);

        $this->artisan('pm-agent:sync-approved-titles')->assertExitCode(0);

        $this->assertTrue(ProjectTask::where('title', 'Approved live only')->value('approved_for_dev'));
        $this->assertFalse(ProjectTask::where('title', 'Not approved anywhere')->value('approved_for_dev'));

        putenv('PM_AGENT_BOARD_TOKEN');
    }

    public function test_never_touches_non_todo_rows(): void
    {
        putenv('PM_AGENT_BOARD_TOKEN=board-secret');
        Http::fake([$this->endpointUrl() => Http::response(['configured' => true, 'titles' => []])]);
        ProjectTask::create(['title' => 'Already done', 'agent_name' => 'Dev Agent', 'status' => 'done', 'approved_for_dev' => true]);

        $this->artisan('pm-agent:sync-approved-titles')->assertExitCode(0);

        $this->assertTrue(ProjectTask::where('title', 'Already done')->value('approved_for_dev'));

        putenv('PM_AGENT_BOARD_TOKEN');
    }
}
