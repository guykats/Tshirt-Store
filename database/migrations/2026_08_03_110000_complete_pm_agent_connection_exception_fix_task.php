<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')->insert([
            'epic_id' => null,
            'title' => 'Fix real crash: sync commands didn\'t catch ConnectionException',
            'description' => 'A manually-triggered pm-agent.yml run (#132) hit a transient network failure reaching production (cURL error 28, connect timeout after 10s) and the whole job crashed before "Run PM Agent" even started - not the turn-budget issue diagnosed on the previous task, a completely separate bug. App\\Console\\Commands\\SyncApprovedTaskTitles and SyncEpicDecisions only caught RequestException (a real HTTP 4xx/5xx response) - not Illuminate\\Http\\Client\\ConnectionException (DNS/timeout/network failures), so the uncaught exception exited the artisan command non-zero and, since the workflow step had no continue-on-error, aborted the entire run. Fixed by catching both exception types in both commands, adding an explicit 10s Http::timeout(), and adding continue-on-error: true to both corresponding pm-agent.yml steps as defense in depth. Added test coverage simulating the exact ConnectionException in both command test suites.',
            'agent_name' => 'Dev Agent',
            'task_type' => 'bugfix',
            'status' => 'done',
            'approved_for_dev' => true,
            'commit_sha' => '5fb62b162bc52fc7f55f333a132a8cfeaa9cd9f9',
            'screenshot_path' => null,
            'blocked_reason' => null,
            'completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')->where('title', 'Fix real crash: sync commands didn\'t catch ConnectionException')->delete();
    }
};
