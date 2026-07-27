<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')->insert([
            'epic_id' => null,
            'title' => 'Fix real crash: disable-if-idle curl step had no timeout',
            'description' => 'Investigating a user report of the pm-agent.yml automation toggle staying "enabled" with no approved work, found run #148 hit the same transient cURL connect-timeout (error 28) already hardened against in SyncApprovedTaskTitles/SyncEpicDecisions - but the bare `curl -s -X POST .../disable-if-idle` step in pm-agent.yml had no timeout of its own and hung for 4m31s before failing, silently skipping that run\'s idle-disable check entirely (continue-on-error let the job proceed, but the check never ran). Fixed by adding --max-time 15 --retry 2 --retry-delay 2 --retry-connrefused to the curl invocation, mirroring the Http::timeout(10) pattern already used server-side, so a transient network blip fails fast instead of eating most of the step budget with no result.',
            'agent_name' => 'Ops Agent',
            'task_type' => 'bugfix',
            'status' => 'done',
            'approved_for_dev' => true,
            'commit_sha' => '28904ce42201523700056e74940975eac26c4719',
            'screenshot_path' => null,
            'blocked_reason' => null,
            'completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')->where('title', 'Fix real crash: disable-if-idle curl step had no timeout')->delete();
    }
};
