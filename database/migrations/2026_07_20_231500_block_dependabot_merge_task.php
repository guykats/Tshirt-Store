<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Merge pending Dependabot dependency PRs')
            ->update([
                'status' => 'blocked',
                'commit_sha' => '2e8b723dd9744dfdc507fdf29eeaf3cf2569b738',
                'blocked_reason' => 'Reviewed all 5 open Dependabot PRs (release notes/diffs, green CI). '
                    .'Merged the 2 that succeeded: #3 actions/checkout 6->7 (commit 52808c7) and '
                    .'#5 concurrently 9.2.4->10.0.3 devDependency (commit 2e8b723) - both verified '
                    .'locally afterward (npm ci, npm run build, migrate:fresh --seed, php artisan test, '
                    .'151/151 passing) since the merge push, made with the automation GITHUB_TOKEN, does '
                    .'not itself trigger a new push-triggered tests.yml/deploy.yml run (GITHUB_TOKEN-authored '
                    .'events do not fire other workflow runs). #1 appleboy/ssh-action 1.2.0->1.2.5, '
                    .'#2 actions/setup-node 4->7, and #4 appleboy/scp-action 0.1.7->1.0.0 are also content-safe '
                    .'(reviewed changelogs/action.yml diffs - no relevant breaking changes to the inputs this '
                    .'repo passes) but every merge attempt fails with '
                    .'"GraphQL: refusing to allow a GitHub App to create or update workflow '
                    .'.github/workflows/deploy.yml without workflows permission (mergePullRequest)" because '
                    .'they modify .github/workflows/*.yml and the installed GitHub App/token this session '
                    .'authenticates as lacks the Workflows: write permission GitHub requires for that. '
                    .'This needs either a human to merge #1/#2/#4 via the GitHub UI (their own GitHub '
                    .'identity carries that permission), or the App installation to be granted '
                    .'Workflows: write so future automated runs can merge action-version bumps directly.',
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Merge pending Dependabot dependency PRs')
            ->update([
                'status' => 'in_progress',
                'commit_sha' => null,
                'blocked_reason' => null,
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }
};
