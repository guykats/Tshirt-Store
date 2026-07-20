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
                'title' => 'Guest checkout has no way to look up an order after the browser session ends',
                'description' => 'routes/api.php\'s comment on POST /api/checkout explains that a guest order silently creates and logs in an "unusable-password" guest User so /api/orders and /api/orders/{order} work for the rest of that browser session — but there is no route anywhere in the app (grep confirms no order_number/lookup route exists) for a guest to check order status once that session/cookie is gone (closed browser, different device, cache cleared), since the guest account has no real password to log back in with. A shopper who paid as a guest has zero way to check shipping status or re-download an invoice after that one session. Add a public POST /api/orders/lookup (order_number + email, throttled like the other unauthenticated write/read endpoints — see \'checkout\'/\'forgot-password\' in AppServiceProvider) that returns the order only if both match, and a small "Track Your Order" page/form (no auth) linking from the footer, reusing Orders.jsx\'s existing order-card rendering where practical. Write a feature test: correct order_number+email returns the order, wrong email 404s/403s, and the endpoint is rate-limited.',
                'agent_name' => 'Dev Agent',
                'task_type' => 'feature',
            ],
            [
                'title' => 'No proactive alert when a variant\'s stock hits zero (or the low-stock threshold)',
                'description' => 'App\\Http\\Controllers\\Api\\InventoryController\'s low-stock endpoint (backing Dashboard.jsx\'s "Low Stock" section) is pull-only — an admin only finds out a variant is at or near zero stock by opening /dashboard, there is no push notification anywhere in app/ (grep for stock_quantity across app/ turns up only the checkout decrement and the read-side dashboard query). Add a notification (App\\Notifications\\LowStockAlert or similar, mailed via the existing MAIL_MAILER=log dev setup per this project\'s standing PayPal/SMTP-deferred agreement — no real credentials needed to build and test this) sent to the admin the moment a checkout decrement (CheckoutController::store) takes a variant\'s stock_quantity to 0 or below the existing low-stock threshold, de-duplicated so the same variant doesn\'t re-alert on every subsequent zero-stock attempt. Write a feature test asserting the notification is queued/sent exactly once when a variant crosses the threshold and not again on a second order attempt against the same already-zero variant.',
                'agent_name' => 'Ops Agent',
                'task_type' => 'feature',
            ],
            [
                'title' => 'Transactional order emails are unbranded plain Blade tables',
                'description' => 'All four order emails (resources/views/emails/order-confirmation.blade.php, order-shipped, order-delivered, order-refunded) are bare `<body style="font-family: ...">` with a plain data table and no visual tie to the site at all — no wordmark, no parchment/ink/brass palette, no motif — even though the emails already do the harder locale-aware work (RTL dir switching, Number::currency formatting). This is the one remaining customer touchpoint that doesn\'t read as the same brand as the site (see resources/css/app.css\'s --color-ink/--color-parchment/--color-brass tokens and DesignArt.jsx\'s motifs). Add a shared, email-client-safe (inline styles, no CSS variables — mail clients don\'t support them, hex-code the token values directly) Blade header partial with the wordmark text and a thin brass rule, reused across all four templates via @include, and restyle the existing table to the parchment/ink palette rather than default black-on-white. Keep it simple HTML email markup (tables/inline styles), not a redesign of the content or data shown. No new automated test is feasible for pure visual styling, but include a manual verification note (e.g. a captured render via Laravel\'s Mail::to()->send() in tinker, or a Playwright screenshot of the rendered Blade view) as evidence per this project\'s screenshot convention.',
                'agent_name' => 'Creative Agent',
                'task_type' => 'content',
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
            'Guest checkout has no way to look up an order after the browser session ends',
            'No proactive alert when a variant\'s stock hits zero (or the low-stock threshold)',
            'Transactional order emails are unbranded plain Blade tables',
        ])->delete();
    }
};
