<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Automated production database backups')
            ->update([
                'status' => 'blocked',
                'commit_sha' => '641cfdf2511e3476322c5197459bb7c71445a1ab',
                'blocked_reason' => "The app-side work is fully shipped and verified at 641cfdf2511e3476322c5197459bb7c71445a1ab: config/backup.php, app/Console/Commands/BackupDatabase.php (a daily mysqldump command, writing rotated/retained-14 timestamped dumps outside the git-deployed path, logging SystemEvent 'backup.completed'/'backup.failed'/'backup.rotated' so failures are loud not silent), bootstrap/app.php's withSchedule() wiring it to run dailyAt('03:00'), and a feature test covering success/failure/rotation/directory-creation with Process::fake() (no real mysqldump needed). ".
                    "What could NOT be shipped: the one-time idempotent crontab line in .github/workflows/deploy.yml so cron actually invokes `php artisan schedule:run` every minute in production. This repo's CI-side GitHub App token was rejected by GitHub with 'refusing to allow a GitHub App to create or update workflow .github/workflows/deploy.yml without workflows permission' on every attempt (main and a scratch branch alike) -- this is a hard platform-level permission boundary, not a bug to route around. ".
                    "To unblock: a maintainer with the `workflows` permission (or a human pushing directly) needs to add this block to the end of the 'Recache config, routes and views' SSH step's script in .github/workflows/deploy.yml (after `php artisan view:cache`), which does not touch the existing maintenance-mode trap or any other step: ".
                    "(crontab -l 2>/dev/null | grep -v 'artisan schedule:run'; echo \"* * * * * cd \${{ secrets.DEPLOY_PATH }} && php artisan schedule:run >> /dev/null 2>&1\") | crontab -",
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Automated production database backups')
            ->update([
                'status' => 'in_progress',
                'blocked_reason' => null,
                'updated_at' => now(),
            ]);
    }
};
