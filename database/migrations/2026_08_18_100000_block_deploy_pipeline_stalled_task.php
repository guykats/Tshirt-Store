<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('project_tasks')->insert([
            'title' => 'deploy.yml has not auto-deployed since 2026-08-01 — every autonomous push since is silently not reaching production',
            'description' => "gh run list --workflow=deploy.yml shows no run since commit 8dde7500 (2026-08-01T18:20:33Z), despite 8+ commits landing on main since then (verified via `gh api repos/guykats/Tshirt-Store/compare/8dde7500...667c591`). Root cause: GitHub does not create a new push-triggered workflow run for commits authored via GITHUB_TOKEN/an installation token -- this was already known for workflow-file edits (see CLAUDE.md's installation-token gotcha), but it turns out to block deploy.yml's ordinary push trigger for *every* autonomous commit, not just ones touching .github/workflows/*.yml. Confirmed by commit identity: every push through 8dde7500 was authored/signed as \"Claude\" (verified=true) and triggered a deploy; every push since (committer \"claude[bot]\", verified=false) did not. Concretely this also means production's live project_tasks/epics tables have been frozen since 2026-08-01, so recent runs finding zero approved_for_dev=1 rows were seeing a symptom of the board itself being stale on production, not the owner ignoring the backlog. Tried the standard workaround live (`gh workflow run deploy.yml --ref main`, which GitHub exempts from the anti-recursion rule) -- rejected with HTTP 403 'Resource not accessible by integration', because pm-agent.yml's permissions block grants only contents:write/id-token:write, not actions:write. A durable fix (adding actions:write plus an explicit post-push dispatch step) is itself a workflow-file edit that no autonomous run can currently push either -- this needs a human: (a) immediately unstick production by manually running \"Deploy to Production\" from the Actions tab, or pushing any human-authored commit, and (b) durably fix it via a PAT-backed push credential (repo+workflow scopes) or by applying the actions:write + explicit-dispatch change directly through the GitHub UI. Full writeup in CLAUDE.md's \"Autonomous runs\" section, commit a60ea23f93617709f138dee598160eee5298d0ca.",
            'agent_name' => 'Ops Agent',
            'task_type' => 'bug',
            'status' => 'blocked',
            'commit_sha' => 'a60ea23f93617709f138dee598160eee5298d0ca',
            'screenshot_path' => null,
            'blocked_reason' => 'Needs a human: manually trigger a deploy to unstick production now, and grant a push credential with workflow scope (or apply the actions:write + explicit-dispatch fix via the GitHub UI) for a durable fix — no autonomous run can push the required workflow-file change or dispatch a deploy itself under the current GITHUB_TOKEN permissions.',
            'completed_at' => null,
            'epic_id' => null,
            'approved_for_dev' => false,
            'requested_by' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'deploy.yml has not auto-deployed since 2026-08-01 — every autonomous push since is silently not reaching production')
            ->delete();
    }
};
