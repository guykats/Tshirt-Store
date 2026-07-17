<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')->insert([
            'epic_id' => null,
            'title' => 'Live chat with the Visioner Agent',
            'description' => 'New /dashboard/chat page: a real-time chat backed by a direct server-side call to the Anthropic Messages API (app/Services/AnthropicClient.php). The model has one tool, propose_epic, so a conversation that converges on a concrete idea can be proposed straight onto the Epics board for the owner to choose/reject/delay as usual. Conversation persists in visioner_chat_messages, linked to any epic it proposed. Rate-limited (10/min/admin). Requires ANTHROPIC_API_KEY in the production .env (separate secret from the one used by pm-agent.yml).',
            'agent_name' => 'Dev Agent',
            'task_type' => 'feature',
            'status' => 'done',
            'commit_sha' => '2b1595e0bfa335c973a1a7a5cb47b6b65d918ddc',
            'screenshot_path' => 'task-screenshots/visioner-chat.png',
            'blocked_reason' => null,
            'completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')->where('title', 'Live chat with the Visioner Agent')->delete();
    }
};
