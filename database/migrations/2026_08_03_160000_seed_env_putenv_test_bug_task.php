<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')->insert([
            'title' => "SyncApprovedTaskTitles/SyncEpicDecisions tests use putenv() after boot, which Laravel's env() never sees — 4 tests fail",
            'description' => "tests/Feature/Console/SyncApprovedTaskTitlesCommandTest.php and SyncEpicDecisionsCommandTest.php call putenv('PM_AGENT_BOARD_TOKEN=board-secret') from inside a test method, after TestCase has already booted the app. Laravel's env() helper (Illuminate\\Support\\Env) reads through an adapter chain that checks \$_ENV/\$_SERVER before falling back to getenv() — putenv() only updates the getenv() table, never \$_ENV, so a mid-test putenv() call is invisible to env() once the framework has booted. Confirmed via php artisan tinker: env('PM_AGENT_BOARD_TOKEN') returns '' even immediately after putenv('PM_AGENT_BOARD_TOKEN=board-secret') inside a booted app, while a bare php -r script without Laravel booted sees it fine. Net effect: SyncApprovedTaskTitles::handle() and SyncEpicDecisions::handle() both read \$token === '' in every test that expects a real token, silently take the 'not configured' early-return path, and never call the faked Http endpoint — so 4 tests currently fail: test_revokes_a_migration_only_approval_the_live_titles_no_longer_include, test_grants_a_live_approval_the_migration_history_never_recorded (both SyncApprovedTaskTitlesCommandTest), test_updates_status_and_priority_of_an_existing_epic_to_match_a_live_decision, and test_inserts_a_live_only_epic_that_was_never_migration_seeded (both SyncEpicDecisionsCommandTest). Verified pre-existing and unrelated to any recent change — fails identically in isolation via --filter on a clean checkout. Fix: in each test, set \$_ENV['PM_AGENT_BOARD_TOKEN'] directly (or use Config::set + read board_token through config('services.pm_agent.board_token') in the commands instead of env() directly) so the override is visible post-boot; clean up by unsetting \$_ENV afterward the same way.",
            'agent_name' => 'Dev Agent',
            'status' => 'todo',
            'task_type' => 'bugfix',
            'approved_for_dev' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', "SyncApprovedTaskTitles/SyncEpicDecisions tests use putenv() after boot, which Laravel's env() never sees — 4 tests fail")
            ->delete();
    }
};
