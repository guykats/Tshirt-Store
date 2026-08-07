<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'SyncApprovedTaskTitles/SyncEpicDecisions read PM_AGENT_BOARD_TOKEN via env() directly, so their own tests silently test nothing')
            ->update([
                'description' => "SUPERSEDED — do not build this task as originally written; see task \"SyncApprovedTaskTitles and SyncEpicDecisions no longer actually sync on a clean checkout\" (created from the same underlying test failures) for the confirmed root cause and correct fix. That task's 2026-08-19 investigation found the real cause is the opposite of what this task recommends: env('PM_AGENT_BOARD_TOKEN', '') in both commands is correct and already works fine against a real process-level env var (exactly how pm-agent.yml's step-level `env:` supplies the real secret in CI). The 4 failing tests fail only because they use putenv() to simulate the token mid-process, and in this Laravel 13.20/phpdotenv 5.6 stack env() does not observe a runtime putenv() call — it resolves through phpdotenv's Repository, backed by \$_ENV/\$_SERVER, which putenv() never populates. Switching the commands to read config('services.pm_agent.board_token') instead of env() (this task's original recommendation) would not fix anything, since the tests would still need to set that config value some way env() itself already supports for real env vars. The actual fix is in the tests only: replace putenv('PM_AGENT_BOARD_TOKEN=...') with directly setting \$_ENV['PM_AGENT_BOARD_TOKEN']/\$_SERVER['PM_AGENT_BOARD_TOKEN'] (and unsetting both in teardown), then re-verify all 4 previously-failing tests exercise the real success path. See CLAUDE.md's env()/putenv() gotcha for the general lesson. Original (incorrect) recommendation preserved for context: \"Fix by switching both commands to read config('services.pm_agent.board_token') instead of env() directly, and updating both test files to configure the token via config(['services.pm_agent.board_token' => ...]) instead of putenv(), so the config binding (which tests can reliably override) is the single source of truth already established by base_url's existing pattern.\"",
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'SyncApprovedTaskTitles/SyncEpicDecisions read PM_AGENT_BOARD_TOKEN via env() directly, so their own tests silently test nothing')
            ->update([
                'description' => "app/Console/Commands/SyncApprovedTaskTitles.php:19 and app/Console/Commands/SyncEpicDecisions.php:19 both read the token with \$token = (string) env('PM_AGENT_BOARD_TOKEN', ''), bypassing the config/services.php wrapper ('services.pm_agent.board_token') that config('services.pm_agent.base_url') on the very next lines already uses correctly. Because .env ships PM_AGENT_BOARD_TOKEN= (an empty string, not absent), Dotenv caches that empty value into \$_ENV/\$_SERVER at boot; when the test suite calls putenv('PM_AGENT_BOARD_TOKEN=board-secret') to simulate a configured token (SyncApprovedTaskTitlesCommandTest and SyncEpicDecisionsCommandTest, several cases each), env()'s underlying repository still resolves the boot-time \$_ENV/\$_SERVER value ahead of the live putenv() override, so the command silently takes its 'token not set' early-return path every time — verified directly in tinker: putenv() is set, then env('PM_AGENT_BOARD_TOKEN','') still returns ''. The result: the four tests asserting real revoke/grant sync behavior (2 in each Command test) already fail on main today (confirmed by reproducing on a clean checkout with no other changes), and the tests that claim to cover 'leaves state untouched when production is unreachable' pass only by coincidence, since the untouched-early-return path is identical whether or not a token is genuinely configured — meaning this critical approval-gate-integrity sync mechanism (see CLAUDE.md's PM_AGENT_BOARD_TOKEN section) currently has no real test coverage of its actual revoke/grant logic at all. Fix by switching both commands to read config('services.pm_agent.board_token') instead of env() directly, and updating both test files to configure the token via config(['services.pm_agent.board_token' => ...]) instead of putenv(), so the config binding (which tests can reliably override) is the single source of truth already established by base_url's existing pattern.",
            ]);
    }
};
