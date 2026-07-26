<?php

namespace Tests\Feature\Console;

use App\Models\Epic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Runs inside pm-agent.yml right after migrate:fresh --seed, alongside
 * pm-agent:sync-approved-titles — see App\Console\Commands\SyncEpicDecisions.
 * Epics can be both mutated (approve/reject/delay) AND created entirely live
 * with no migration at all (VisionerChatController::proposeEpic), so this
 * command has to both update existing local rows and insert missing ones.
 */
class SyncEpicDecisionsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // A backfill migration seeds real Visioner Agent epics on every fresh
        // migration (including in tests via RefreshDatabase) — clear the slate.
        Epic::query()->delete();
    }

    protected function endpointUrl(): string
    {
        return 'https://store.guykats.com/api/pm-agent-automation/epic-decisions';
    }

    public function test_leaves_epics_untouched_when_no_board_token_is_set(): void
    {
        putenv('PM_AGENT_BOARD_TOKEN');
        Epic::create(['title' => 'Migration-seeded epic', 'agent_name' => 'Visioner Agent', 'status' => 'proposed']);

        $this->artisan('pm-agent:sync-epic-decisions')->assertExitCode(0);

        $this->assertSame('proposed', Epic::where('title', 'Migration-seeded epic')->value('status'));
        Http::assertNothingSent();
    }

    public function test_leaves_epics_untouched_when_production_is_unreachable(): void
    {
        putenv('PM_AGENT_BOARD_TOKEN=board-secret');
        Http::fake([$this->endpointUrl() => Http::response(['message' => 'Server error'], 500)]);
        Epic::create(['title' => 'Migration-seeded epic', 'agent_name' => 'Visioner Agent', 'status' => 'proposed']);

        $this->artisan('pm-agent:sync-epic-decisions')->assertExitCode(0);

        $this->assertSame('proposed', Epic::where('title', 'Migration-seeded epic')->value('status'));

        putenv('PM_AGENT_BOARD_TOKEN');
    }

    public function test_leaves_epics_untouched_when_production_is_unreachable_over_the_network(): void
    {
        // Same real crash SyncApprovedTaskTitlesCommandTest covers: a
        // ConnectionException (DNS/timeout/network) is a different exception
        // type than RequestException (a real HTTP 4xx/5xx) and must be
        // caught too, or the whole pm-agent.yml job aborts before the PM
        // Agent starts.
        putenv('PM_AGENT_BOARD_TOKEN=board-secret');
        Http::fake([$this->endpointUrl() => function () {
            throw new ConnectionException('cURL error 28: Failed to connect to store.guykats.com port 443 after 10001 ms: Timeout was reached');
        }]);
        Epic::create(['title' => 'Migration-seeded epic', 'agent_name' => 'Visioner Agent', 'status' => 'proposed']);

        $this->artisan('pm-agent:sync-epic-decisions')->assertExitCode(0);

        $this->assertSame('proposed', Epic::where('title', 'Migration-seeded epic')->value('status'));

        putenv('PM_AGENT_BOARD_TOKEN');
    }

    public function test_updates_status_and_priority_of_an_existing_epic_to_match_a_live_decision(): void
    {
        putenv('PM_AGENT_BOARD_TOKEN=board-secret');
        Epic::create(['title' => 'Migration-seeded epic', 'agent_name' => 'Visioner Agent', 'status' => 'proposed', 'priority' => 5]);
        Http::fake([$this->endpointUrl() => Http::response([
            'configured' => true,
            'epics' => [['title' => 'Migration-seeded epic', 'description' => null, 'agent_name' => 'Visioner Agent', 'status' => 'approved', 'priority' => 5]],
        ])]);

        $this->artisan('pm-agent:sync-epic-decisions')->assertExitCode(0);

        $this->assertSame('approved', Epic::where('title', 'Migration-seeded epic')->value('status'));

        putenv('PM_AGENT_BOARD_TOKEN');
    }

    public function test_inserts_a_live_only_epic_that_was_never_migration_seeded(): void
    {
        putenv('PM_AGENT_BOARD_TOKEN=board-secret');
        Http::fake([$this->endpointUrl() => Http::response([
            'configured' => true,
            'epics' => [['title' => 'Proposed via live chat', 'description' => 'From the Visioner conversation', 'agent_name' => 'Visioner Agent', 'status' => 'proposed', 'priority' => 0]],
        ])]);

        $this->assertDatabaseMissing('epics', ['title' => 'Proposed via live chat']);

        $this->artisan('pm-agent:sync-epic-decisions')->assertExitCode(0);

        $this->assertDatabaseHas('epics', ['title' => 'Proposed via live chat', 'status' => 'proposed']);

        putenv('PM_AGENT_BOARD_TOKEN');
    }
}
