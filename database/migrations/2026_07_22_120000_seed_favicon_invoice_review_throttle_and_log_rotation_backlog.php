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
                'title' => 'public/favicon.ico is a 0-byte empty file',
                'description' => 'ls -la public/favicon.ico confirms the file that resources/views/app.blade.php\'s `<link rel="icon" href="/favicon.ico" sizes="any">` points at exists but is literally 0 bytes — every browser tab, bookmark, and mobile home-screen shortcut for the site renders a blank/broken icon instead of any brand mark. public/og-image.png (used by the og:image/twitter:image tags in the same file) is a real, non-empty asset, so this looks like an accidental empty placeholder that was never replaced. Generate a real favicon derived from the site\'s existing restrained line-art language (see resources/js/components/DesignArt.jsx\'s star-of-david or chai motifs, resources/css/app.css\'s --color-ink/--color-parchment/--color-brass tokens) rather than inventing new artwork — e.g. render the Star of David line-art on a parchment background at a few square sizes and encode it as a proper multi-resolution .ico (16/32/48px) at public/favicon.ico, replacing the empty file. Verify by loading the built site in a browser (or checking the file\'s magic bytes/size are no longer 0) and include that as evidence.',
                'agent_name' => 'Creative Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => 'Order invoice PDF (resources/views/invoices/order.blade.php) is unbranded, unlike the order emails it\'s attached to',
                'description' => 'Now that the four order emails (resources/views/emails/order-*.blade.php, see the "Transactional order emails are unbranded plain Blade tables" task) carry the site\'s parchment/ink/brass palette and wordmark via a shared header partial, the invoice PDF that OrderConfirmationMail actually attaches to the confirmation email (resources/views/invoices/order.blade.php, rendered to PDF — check app/Mail/OrderConfirmationMail.php for how it\'s generated/attached) is the one remaining piece of that same customer touchpoint still styled as plain black-on-white (`color: #1b1b18`, `background: #f5f5f4` header cells, `#ddd` borders — default, not brand tokens). Reuse the same hex-coded parchment/ink/brass values (see resources/css/app.css for the source tokens: --color-ink #17140f, --color-parchment #f7f4ee, --color-brass #8c6a3f, --color-line #e4dfd4) and the same wordmark treatment established in resources/views/emails/partials/header.blade.php, adapted for a print/PDF context (the invoice is rendered via whatever PDF engine app/Mail/OrderConfirmationMail.php already uses — check its constraints, e.g. DejaVu Sans font support, before assuming full CSS support). Keep the existing locale-aware RTL/LTR and Number::currency formatting logic untouched — this is a visual restyle only. Verify by rendering the invoice view (or generating an actual PDF) and capturing a screenshot as evidence, same convention as the order-email task.',
                'agent_name' => 'Creative Agent',
                'task_type' => 'content',
            ],
            [
                'title' => 'Password-reset and low-stock-alert emails still render in Laravel\'s stock blue-button theme, clashing with the newly-branded order emails',
                'description' => 'app/Notifications/ResetPasswordNotification.php and app/Notifications/LowStockAlert.php both build their email via Laravel\'s built-in `(new MailMessage)->line()->action()` markdown-mail API, which renders through Laravel\'s default vendor mail theme (resources/views/vendor/mail if published, otherwise the framework\'s built-in default — a white card with a blue action button and generic sans-serif type). Now that the four order-status emails (order-confirmation/shipped/delivered/refunded) carry the site\'s parchment/ink/brass identity via resources/views/emails/partials/header.blade.php, these two notification-driven emails are the visibly odd ones out — a shopper resetting their password or an admin getting a low-stock alert sees a completely different, generic-Laravel-feeling email in the same inbox thread as the branded ones. Run `php artisan vendor:publish --tag=laravel-mail` to get resources/views/vendor/mail/html/themes/default.css locally, then override the theme\'s CSS custom properties/hex values with this project\'s tokens (see resources/css/app.css: --color-ink #17140f, --color-parchment #f7f4ee, --color-brass #8c6a3f for the button/link color, --color-line #e4dfd4) — inline styles only, same email-client-safe constraint as the order-email task, since Laravel\'s markdown mail theme is itself compiled down to inline styles at send time. Do not change either notification\'s actual content/lines, just the theme it renders through. No new automated test is feasible for pure visual styling; render both notifications via `->toMail()->render()` (or `Notification::fake` isn\'t useful here — actually call it) and capture a Playwright screenshot as evidence, same convention as the order-email task.',
                'agent_name' => 'Creative Agent',
                'task_type' => 'content',
            ],
            [
                'title' => 'POST/DELETE /api/products/{product}/reviews have no rate limiting despite being public-write endpoints',
                'description' => 'routes/api.php throttles every other unauthenticated or spam-prone write (checkout, register, login, forgot-password, order-lookup, visioner-chat — see the named buckets in app/Providers/AppServiceProvider.php\'s RateLimiter::for calls) but POST /api/products/{product}/reviews and DELETE /api/products/{product}/reviews/{review} (App\\Http\\Controllers\\Api\\ReviewController::store/destroy) have no throttle middleware at all. They do require auth:sanctum and ReviewController::store does enforce a real proof-of-purchase check (purchaseOrderFor()) before allowing a review, which limits abuse somewhat — but a single authenticated account with one qualifying paid order can still hammer the endpoint (e.g. after a duplicate-review 422, or just as a general availability/log-noise concern) with no cap, unlike every comparable write path in this app. Add a RateLimiter::for(\'reviews\', ...) bucket (keyed by user id, matching the pattern used for \'visioner-chat\') and apply throttle:reviews to both routes. Write a feature test asserting repeated review submissions past the limit get throttled (429), following the pattern of the existing checkout-rate-limit test.',
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => 'Production log file (storage/logs/laravel.log) has no rotation or retention, unlike the DB backup job',
                'description' => 'config/logging.php\'s \'stack\' channel (the default via LOG_CHANNEL=stack in .env.example) resolves `LOG_STACK` to \'single\' by default (`explode(\',\', (string) env(\'LOG_STACK\', \'single\'))`), which uses Monolog\'s StreamHandler writing forever to a single storage/logs/laravel.log with no size cap, no rotation, and no pruning — in sharp contrast to app/Console/Commands/BackupDatabase.php\'s explicit 14-day retention/rotation for DB backups on the same box (see bootstrap/app.php\'s withSchedule). On a long-running production deploy this file only grows, and nothing before this task ever truncates or archives it, which is a real disk-exhaustion risk over months of uptime. The \'daily\' channel already exists in config/logging.php and already reads a configurable retention window (`env(\'LOG_DAILY_DAYS\', 14)`) — it\'s just not the default. Change the \'stack\' channel\'s default LOG_STACK value from \'single\' to \'daily\' so a fresh deploy (or one that hasn\'t explicitly set LOG_STACK in its .env) gets bounded, rotated logs without any production .env change being required, and document in a code comment why (mirroring the backup job\'s rotation rationale). Add a config-level test (e.g. asserting config(\'logging.channels.stack.channels\') resolves to [\'daily\'] when LOG_STACK is unset) so a future refactor can\'t silently regress this.',
                'agent_name' => 'Ops Agent',
                'task_type' => 'chore',
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
            'public/favicon.ico is a 0-byte empty file',
            'Order invoice PDF (resources/views/invoices/order.blade.php) is unbranded, unlike the order emails it\'s attached to',
            'Password-reset and low-stock-alert emails still render in Laravel\'s stock blue-button theme, clashing with the newly-branded order emails',
            'POST/DELETE /api/products/{product}/reviews have no rate limiting despite being public-write endpoints',
            'Production log file (storage/logs/laravel.log) has no rotation or retention, unlike the DB backup job',
        ])->delete();
    }
};
