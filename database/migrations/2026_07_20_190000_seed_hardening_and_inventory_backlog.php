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
                'title' => 'Security response headers (CSP, HSTS, X-Frame-Options)',
                'description' => 'Grepping app/Http/Middleware and config/ turns up no Content-Security-Policy, Strict-Transport-Security, X-Frame-Options, or X-Content-Type-Options headers anywhere — bootstrap/app.php registers throttle and Sanctum middleware but nothing for response security headers, despite this being a real payment site (PayPal checkout, Sanctum session cookies). Add a SecurityHeaders middleware (registered globally in bootstrap/app.php) that sets a reasonable CSP (allowing self + the PayPal SDK domains checkout already loads — check resources/js for the PayPal script src), Strict-Transport-Security, X-Frame-Options: DENY, X-Content-Type-Options: nosniff, and Referrer-Policy. Write a feature test asserting these headers are present on a sample response.',
                'agent_name' => 'Ops Agent',
                'task_type' => 'security',
            ],
            [
                'title' => 'Low-stock inventory alerts for admins',
                'description' => 'ProductVariant has a stock_quantity column that CheckoutController decrements on purchase and blocks checkout at zero, but there is no admin-facing visibility into stock levels anywhere in resources/js/pages/Dashboard* — an admin only discovers a stockout when a customer cannot check out. Add a "Low stock" panel/widget to the dashboard (e.g. a new GET /api/inventory/low-stock endpoint, admin-only via auth:sanctum, returning variants below a threshold such as 5) with a React component listing product name, variant (size/color), and remaining quantity, linked from the existing dashboard nav. Write a feature test covering the threshold filtering and that it is admin-only.',
                'agent_name' => 'Dev Agent',
                'task_type' => 'feature',
            ],
            [
                'title' => 'Uptime health-check endpoint',
                'description' => 'There is no /api/health or /up style endpoint anywhere in routes/web.php or routes/api.php, so nothing external (Hostinger monitoring, an uptime pinger, or the deploy.yml pipeline itself) can verify the app is actually serving after a deploy beyond a raw HTTP 200 on "/". Add a lightweight GET /api/health route that checks DB connectivity (a trivial query) and returns JSON {"status":"ok","checks":{"database":"ok"}} on success or a 503 with the failing check on failure, with no auth required. Write a feature test for both the healthy path and a case where a check is stubbed to fail.',
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
            'Security response headers (CSP, HSTS, X-Frame-Options)',
            'Low-stock inventory alerts for admins',
            'Uptime health-check endpoint',
        ])->delete();
    }
};
