<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function title(): string
    {
        return 'SyncApprovedTaskTitles and SyncEpicDecisions no longer actually sync on a clean checkout — 4 tests fail reproducibly, independent of any other change';
    }

    private function originalDescription(): string
    {
        return "Running the full suite on a clean `migrate:fresh --seed` checkout (no code changes) reproducibly fails 4 tests: `SyncApprovedTaskTitlesCommandTest::test_revokes_a_migration_only_approval_the_live_titles_no_longer_include` and `::test_grants_a_live_approval_the_migration_history_never_recorded` (both assert on `ProjectTask::approved_for_dev` after `php artisan pm-agent:sync-approved-titles`, both get the pre-sync value back untouched), plus `SyncEpicDecisionsCommandTest::test_updates_status_and_priority_of_an_existing_epic_to_match_a_live_decision` and `::test_inserts_a_live_only_epic_that_was_never_migration_seeded` (same untouched-after-sync pattern on `epics`). This was discovered incidentally while verifying an unrelated backlog-seeding migration and confirmed unrelated to it by re-running with that migration removed -- same 4 failures either way. These two commands (`app/Console/Commands/SyncApprovedTaskTitles.php`, `app/Console/Commands/SyncEpicDecisions.php`) are exactly the ones `pm-agent.yml`'s \"Sync real approved-task titles from production\" / \"Sync real epic decisions from production\" steps depend on (per CLAUDE.md's `PM_AGENT_BOARD_TOKEN` section) to reconcile the CI job's replayed local SQLite against real live dashboard approvals before every autonomous run decides what to build -- if this regression is live in production too (not just these tests), the PM Agent may currently be deciding what to build off stale/replayed approval state instead of the real one. `config('services.pm_agent.base_url')` already defaults correctly to `https://store.guykats.com` (config/services.php:74), which rules out a URL-mismatch cause -- both failing tests only diverge from passing tests in this file by requiring the sync's DB-update logic (SyncApprovedTaskTitles.php:49-52 / the equivalent in SyncEpicDecisions.php) to actually execute rather than hitting one of the earlier early-return branches (no token / unreachable production), so start by instrumenting whether `env('PM_AGENT_BOARD_TOKEN', '')` is actually observing the tests' `putenv('PM_AGENT_BOARD_TOKEN=board-secret')` calls in this Laravel/PHPUnit version, since the token-present request-succeeds path is the one path these two tests exercise that the other (passing) tests in the same file don't.";
    }

    private function updatedDescription(): string
    {
        return "UPDATE from a 2026-08-19 investigation: confirmed root cause, and it is NOT the production-impacting regression the original description worried about. `php artisan tinker --execute=\"putenv('PM_AGENT_BOARD_TOKEN=x'); var_dump(getenv('PM_AGENT_BOARD_TOKEN')); var_dump(env('PM_AGENT_BOARD_TOKEN'));\"` shows `getenv()` sees the putenv()'d value but Laravel's `env()` helper returns `''` regardless -- in this Laravel 13.20/phpdotenv 5.6 stack, `env()` resolves via phpdotenv's Repository (backed by `\$_ENV`/`\$_SERVER`), which a runtime `putenv()` call does not populate. But `PM_AGENT_BOARD_TOKEN=x php artisan tinker --execute=\"var_dump(env('PM_AGENT_BOARD_TOKEN'));\"` (a real process-level env var, set *before* the PHP process starts) IS observed correctly. That second form is exactly how pm-agent.yml supplies the real secret -- its \"Sync real approved-task titles/epic decisions from production\" steps set `PM_AGENT_BOARD_TOKEN: \${{ secrets.PM_AGENT_BOARD_TOKEN }}` in the step's `env:` block, a genuine OS-level env var for that `php artisan pm-agent:sync-approved-titles` invocation, not a runtime `putenv()` call inside an already-running process. So the real sync commands almost certainly work fine in actual CI runs; only these 4 tests' use of `putenv()` to *simulate* the token mid-process fails to be observed by `env()`, making the token-present code path untestable as currently written, not broken. The fix is in the tests, not `SyncApprovedTaskTitles.php`/`SyncEpicDecisions.php`: replace `putenv('PM_AGENT_BOARD_TOKEN=board-secret')` with something `env()` actually reads in this stack (e.g. set `\$_ENV['PM_AGENT_BOARD_TOKEN']`/`\$_SERVER['PM_AGENT_BOARD_TOKEN']` directly and unset them in teardown), then re-verify all 4 previously-failing tests actually exercise the success path (not just pass by coincidence). Do not 'fix' this by changing the command's own token-reading logic -- that logic is correct and already proven to work against a real process-level env var above. See CLAUDE.md's new gotcha on this env()/putenv() mismatch for the general lesson. Original description preserved below for context.\n\n---\n\n".$this->originalDescription();
    }

    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', $this->title())
            ->update([
                'description' => $this->updatedDescription(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', $this->title())
            ->update([
                'description' => $this->originalDescription(),
            ]);
    }
};
