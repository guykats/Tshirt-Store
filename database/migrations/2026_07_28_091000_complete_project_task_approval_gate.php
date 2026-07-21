<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')->insert([
            'epic_id' => null,
            'title' => 'Add a human approval gate for project_tasks so agents only build owner-approved work',
            'description' => 'The owner asked that the autonomous loop (interactive sessions and the pm-agent cron alike) only ever pick up todo tasks they have explicitly approved for development, with a button on the board to grant that approval. Added an approved_for_dev boolean (default false) on project_tasks, admin-only POST /api/project-tasks/{id}/approve and /unapprove endpoints (logged as project_task.approved/unapproved system events), and an "Approve for development" toggle on todo rows in /dashboard/progress (green "Approved" badge once granted). Updated .claude/skills/ship-project-task/SKILL.md (new step 0) and .github/workflows/pm-agent.yml\'s prompt so both interactive and unattended runs only build status=todo AND approved_for_dev=1 tasks - newly seeded backlog items and tasks freshly broken out of an approved epic are left unapproved by design, pending the owner\'s review.',
            'agent_name' => 'Dev Agent',
            'task_type' => 'feature',
            'status' => 'done',
            'approved_for_dev' => true,
            'commit_sha' => '05ae178320c4d4ed3e94b9df4326429d48d1158d',
            'screenshot_path' => 'task-screenshots/project-task-approval-gate.png',
            'blocked_reason' => null,
            'completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')->where('title', 'Add a human approval gate for project_tasks so agents only build owner-approved work')->delete();
    }
};
