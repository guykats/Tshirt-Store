<?php

namespace App\Console\Commands;

use App\Models\Epic;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

#[Signature('pm-agent:sync-epic-decisions')]
#[Description('Runs inside pm-agent.yml alongside pm-agent:sync-approved-titles. Epics have the same live-click-invisible-to-CI problem as task approvals (approve/reject/delay on the live Epics tab writes straight to production), plus epics can be created live too with no migration at all (VisionerChatController::proposeEpic). Fetches the real epics table from production and reconciles this run\'s local replica - updating status/priority for epics that exist locally, inserting ones that only exist live.')]
class SyncEpicDecisions extends Command
{
    public function handle(): int
    {
        $token = (string) env('PM_AGENT_BOARD_TOKEN', '');

        if ($token === '') {
            $this->warn('PM_AGENT_BOARD_TOKEN not set for this run — leaving migration-replayed epics as-is.');

            return self::SUCCESS;
        }

        $baseUrl = config('services.pm_agent.base_url');

        try {
            $response = Http::timeout(10)->withHeaders(['X-PM-Agent-Token' => $token])
                ->get("{$baseUrl}/api/pm-agent-automation/epic-decisions")
                ->throw();
        } catch (ConnectionException|RequestException $e) {
            // ConnectionException (DNS/timeout/network) is a real, transient
            // failure mode, not just RequestException (4xx/5xx) - a run
            // already crashed the whole workflow over exactly this (cURL
            // error 28 talking to production) before this fix.
            $this->warn('Could not reach production for real epic state — leaving migration-replayed epics as-is.');

            return self::SUCCESS;
        }

        $epics = $response->json('epics') ?? [];
        $updated = 0;
        $inserted = 0;

        foreach ($epics as $epic) {
            $title = $epic['title'] ?? null;

            if (! $title) {
                continue;
            }

            $existing = Epic::where('title', $title)->first();

            if ($existing) {
                $existing->update([
                    'status' => $epic['status'] ?? $existing->status,
                    'priority' => $epic['priority'] ?? $existing->priority,
                ]);
                $updated++;

                continue;
            }

            // Only exists live (proposed via chat, never migration-seeded). Inserting
            // it here gives it a LOCAL, this-run-only id - if the PM Agent acts on
            // this epic (e.g. breaking an approved one into tasks), it must first
            // write a real migration inserting the epic itself before referencing
            // its id in any project_tasks migration, or the reference will dangle
            // on the next fresh replay. See the workflow prompt's step 3.
            Epic::create([
                'title' => $title,
                'description' => $epic['description'] ?? null,
                'agent_name' => $epic['agent_name'] ?? 'Visioner Agent',
                'status' => $epic['status'] ?? 'proposed',
                'priority' => $epic['priority'] ?? 0,
            ]);
            $inserted++;
        }

        $this->info("Synced {$updated} epic(s), inserted {$inserted} live-only epic(s) from production.");

        return self::SUCCESS;
    }
}
