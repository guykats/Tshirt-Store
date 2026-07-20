<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Nothing in deploy.yml actually installs the crontab entry that bootstrap/app.php\'s comment claims exists — scheduled jobs may never run in production')
            ->update([
                'status' => 'blocked',
                'blocked_reason' => 'Duplicate of task 65 ("Automated production database backups"), already blocked on the exact same fix: an idempotent crontab line for `php artisan schedule:run` needs to be added to .github/workflows/deploy.yml\'s SSH step, and every attempt to push a change to any .github/workflows/*.yml file (this task, task 65, task 67, and task 69 all hit it independently) is rejected by GitHub with "refusing to allow a GitHub App to create or update workflow .github/workflows/*.yml without workflows permission" — a hard platform-level wall on this session\'s GitHub App token, not something to keep rediscovering. See task 65\'s blocked_reason for the exact 3-line diff to apply to deploy.yml once unblocked. To unblock: a maintainer with the `workflows` permission (or a human pushing directly) needs to add that crontab-install step to deploy.yml themselves, or grant the App installation Workflows: write. Once that one shared fix lands, it resolves tasks 65, 67, 69, and this one simultaneously — no need to duplicate the work per task.',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Nothing in deploy.yml actually installs the crontab entry that bootstrap/app.php\'s comment claims exists — scheduled jobs may never run in production')
            ->update([
                'status' => 'todo',
                'blocked_reason' => null,
                'updated_at' => now(),
            ]);
    }
};
