<?php

namespace App\Console\Commands;

use App\Models\ProjectTask;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

#[Signature('pm-agent:sync-approved-titles')]
#[Description('Runs inside pm-agent.yml, after migrate:fresh --seed, and before the PM Agent starts deciding what to build. This CI job only ever sees a disposable local SQLite rebuilt from migration history, which has no idea a human approved a task from the real dashboard - this fetches the real, live set of approved todo task titles from production and reconciles this run\'s local database to match, so task-selection reflects real owner approvals instead of only what migration history encodes.')]
class SyncApprovedTaskTitles extends Command
{
    public function handle(): int
    {
        $token = (string) env('PM_AGENT_BOARD_TOKEN', '');

        if ($token === '') {
            $this->warn('PM_AGENT_BOARD_TOKEN not set for this run — leaving migration-replayed approvals as-is.');

            return self::SUCCESS;
        }

        $baseUrl = config('services.pm_agent.base_url');

        try {
            $response = Http::withHeaders(['X-PM-Agent-Token' => $token])
                ->get("{$baseUrl}/api/pm-agent-automation/approved-todo-titles")
                ->throw();
        } catch (RequestException $e) {
            $this->warn('Could not reach production for real approval state — leaving migration-replayed approvals as-is.');

            return self::SUCCESS;
        }

        $titles = $response->json('titles') ?? [];

        // Reset first: a title the migration history marked approved but that
        // is no longer in production's live list (e.g. the owner clicked
        // "unapprove" after an earlier session encoded an approval into a
        // migration) must not stay approved locally either.
        ProjectTask::query()->where('status', 'todo')->update(['approved_for_dev' => false]);

        if ($titles !== []) {
            ProjectTask::query()->where('status', 'todo')->whereIn('title', $titles)->update(['approved_for_dev' => true]);
        }

        $this->info(count($titles).' real approved todo task(s) synced from production.');

        return self::SUCCESS;
    }
}
