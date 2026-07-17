<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $backlog = [
            ['title' => 'Forgot password / reset flow', 'description' => 'No way for a user to recover their account if they forget their password. Build it on top of Laravel\'s built-in password-reset support (works with MAIL_MAILER=log until real SMTP creds arrive).', 'agent_name' => 'Dev Agent', 'task_type' => 'feature'],
            ['title' => 'Global error boundary', 'description' => 'No React error boundary exists anywhere. If any page component throws, the customer gets a blank white screen with no way to recover instead of a friendly error message.', 'agent_name' => 'Dev Agent', 'task_type' => 'bugfix'],
            ['title' => 'Catalog search / filter', 'description' => 'No way to search or filter the catalog. Fine at 7 products, a real gap once the catalog grows past a page or two.', 'agent_name' => 'Dev Agent', 'task_type' => 'feature'],
            ['title' => 'Product structured data (JSON-LD)', 'description' => 'Product pages have no schema.org markup, so Google can\'t show rich results (price, availability) in search. Builds on the OG tags work.', 'agent_name' => 'Creative Agent', 'task_type' => 'feature'],
            ['title' => 'Enable Dependabot', 'description' => 'No automated dependency update checks exist for composer.json or package.json. A one-file config, real security/maintenance hygiene.', 'agent_name' => 'Ops Agent', 'task_type' => 'infra'],
        ];

        foreach ($backlog as $task) {
            DB::table('project_tasks')->insert(array_merge([
                'commit_sha' => null,
                'screenshot_path' => null,
                'blocked_reason' => null,
                'completed_at' => null,
            ], $task, [
                'status' => 'todo',
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    public function down(): void
    {
        DB::table('project_tasks')->where('status', 'todo')->delete();
    }
};
