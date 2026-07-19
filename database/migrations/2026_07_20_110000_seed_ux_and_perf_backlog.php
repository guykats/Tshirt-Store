<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $backlog = [
            [
                'title' => '404 / not-found page for unmatched SPA routes',
                'description' => 'The React Router config in resources/js/App.jsx lists explicit routes (/, /about, /products/:slug, /login, /register, /forgot-password, /reset-password, /checkout/:productId) but has no catch-all "*" route — visiting any other URL renders a blank page instead of a friendly not-found page. Add a bilingual 404 page and wire it as the catch-all route.',
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => 'Account settings: change password while logged in',
                'description' => 'A logged-in customer can only change their password via the forgot-password email flow (PasswordResetController) — there is no self-service "change my password" while authenticated. Add an account settings area with a change-password form (current password + new password confirmation) using Sanctum auth, with the same bilingual/accessible form conventions as Login/Register.',
                'agent_name' => 'Dev Agent',
                'task_type' => 'feature',
            ],
            [
                'title' => 'Add database indexes on high-traffic query columns',
                'description' => 'products.status (filtered on every catalog/product-detail query, see ProductController) and orders.status/orders.user_id (filtered on every order-history query, see OrderController) have no explicit index beyond primary/foreign keys. Add migrations to index these columns to keep query latency low as the catalog and order volume grow — complements the catalog response caching shipped in commit 28fb3d23894200538c7e7aabbe2ab2e360aa4292, which still needs a fast query on every cache miss.',
                'agent_name' => 'Ops Agent',
                'task_type' => 'infra',
            ],
        ];

        foreach ($backlog as $task) {
            DB::table('project_tasks')->insert(array_merge([
                'epic_id' => null,
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
        DB::table('project_tasks')->whereIn('title', [
            '404 / not-found page for unmatched SPA routes',
            'Account settings: change password while logged in',
            'Add database indexes on high-traffic query columns',
        ])->delete();
    }
};
