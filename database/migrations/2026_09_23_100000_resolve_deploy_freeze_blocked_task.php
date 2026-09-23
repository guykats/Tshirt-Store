<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'deploy.yml has not auto-deployed since 2026-08-01 — every autonomous push since is silently not reaching production')
            ->update([
                'status' => 'done',
                'blocked_reason' => null,
                'commit_sha' => '7714186',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'deploy.yml has not auto-deployed since 2026-08-01 — every autonomous push since is silently not reaching production')
            ->update([
                'status' => 'blocked',
                'blocked_reason' => 'Needs a human: manually trigger a deploy to unstick production now, and grant a push credential with workflow scope (or apply the actions:write + explicit-dispatch fix via the GitHub UI) for a durable fix — no autonomous run can push the required workflow-file change or dispatch a deploy itself under the current GITHUB_TOKEN permissions.',
                'commit_sha' => 'a60ea23f93617709f138dee598160eee5298d0ca',
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }
};
