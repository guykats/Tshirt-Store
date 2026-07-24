<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')->insert([
            'epic_id' => null,
            'title' => 'Add an admin control to enable/disable the PM Agent GitHub Action from Team Management',
            'description' => 'The owner asked for a button in Team Management to enable/disable the pm-agent.yml GitHub Action automation without needing to go to GitHub. Added app/Services/GitHubActionsClient.php (wraps the GitHub REST API - GET .../actions/workflows/{file} for current state, PUT .../enable and .../disable to toggle) and PmAgentAutomationController (admin-only GET /api/pm-agent-automation, POST .../enable, POST .../disable, both logged as pm_agent.enabled/disabled system events). A control panel on the Board page (/dashboard/progress) shows the current state and a toggle button. This needs a real GitHub personal access token (GITHUB_ACTIONS_TOKEN, classic with workflow scope or fine-grained with Actions: Read and write on this repo) added to production .env to actually function - same deferred-credential pattern as PayPal/SMTP/Anthropic. Until that token is added, the control shows a clear "not configured" message and does nothing destructive (verified both states with Http::fake in tests and a real screenshot of the current, unconfigured production-matching state).',
            'agent_name' => 'Dev Agent',
            'task_type' => 'feature',
            'status' => 'done',
            'approved_for_dev' => true,
            'commit_sha' => '0c4a194ec025ed4dc2f3089448d2c07fe5337f1a',
            'screenshot_path' => 'task-screenshots/pm-agent-automation-control.png',
            'blocked_reason' => null,
            'completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')->where('title', 'Add an admin control to enable/disable the PM Agent GitHub Action from Team Management')->delete();
    }
};
