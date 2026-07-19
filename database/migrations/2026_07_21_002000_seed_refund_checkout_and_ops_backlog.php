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
                'title' => 'Admin order refund flow',
                'description' => 'The orders.status enum (see 2026_07_16_100500_create_orders_table.php) includes \'refunded\' and payment_status includes \'refunded\' too, but nothing in the codebase ever sets either — there is no refund endpoint, no admin UI control, and App\Services\PayPalClient (createOrder/captureOrder/getOrder/verifyWebhookSignature) has no refund method, even though the captured PayPal capture id is already saved on orders.paypal_transaction_id at capture time (see CheckoutController::capture). Add PayPalClient::refundCapture(string $captureId, ?float $amount = null) hitting PayPal\'s POST /v2/payments/captures/{id}/refund, an admin-only endpoint (e.g. POST /api/orders/{order}/refund) restricted to orders whose payment_status is \'paid\', that calls it, sets order status/payment_status to refunded, logs a SystemEvent like the existing approve/cancel/advance-status actions do, sends a new bilingual OrderRefundedMail (follow OrderConfirmationMail\'s pattern), and add the admin dashboard control next to the existing approve/cancel/advance-status controls. Write feature tests mocking PayPalClient with $this->mock() (the existing pattern in tests/Feature/Api/CheckoutTest.php, not Http::fake) covering: admin can refund a paid order, refunding an unpaid/already-refunded/cancelled order is rejected, non-admin is forbidden, and the mail is queued (Mail::fake()).',
                'agent_name' => 'Dev Agent',
                'task_type' => 'feature',
            ],
            [
                'title' => 'Guest checkout (account-optional purchase flow)',
                'description' => 'POST /api/checkout is currently locked behind the auth:sanctum middleware group in routes/api.php, so a shopper must register an account before they can buy anything — a well-known conversion killer for small apparel stores. orders.user_id is currently NOT NULL with a restrictOnDelete foreign key (2026_07_16_100500_create_orders_table.php), so making the column nullable and reworking every place that assumes an authenticated order owner (OrderController authorization, Orders.jsx "my orders" listing, order confirmation email lookup, invoice access) is a real schema/authorization change, not a trivial route move — evaluate whether it is safer to (a) make user_id nullable and add a guest email/lookup-token path for order status/invoice access, or (b) silently create a normal (unusable-password or invited) User record behind the scenes at guest checkout time so the rest of the app\'s user_id-based logic keeps working unmodified, and pick whichever is lower-risk after reading CheckoutController, OrderController, and the orders/users foreign keys. Whichever approach is chosen, the guest must be able to check out with just an email + shipping address and receive the same OrderConfirmationMail, and must not be able to see other users\' orders. Write feature tests for: guest checkout succeeds and creates a valid order, guest cannot access another user\'s (or another guest\'s) order/invoice, and existing authenticated checkout still works unchanged.',
                'agent_name' => 'Dev Agent',
                'task_type' => 'feature',
            ],
            [
                'title' => 'Size guide page',
                'description' => 'There is no sizing reference anywhere on the site — the new FAQ page\'s sizing/fit answer (resources/js/pages/Faq.jsx) necessarily stays generic because there\'s nowhere to link a real chart. Add a bilingual /size-guide static page following the same established pattern as About.jsx/Privacy.jsx/Terms.jsx/Faq.jsx (useDocumentMeta, DesignArt motif, parchment/ink/brass styling): a real garment size chart (chest/length/sleeve measurements in cm and inches, by S/M/L/XL/XXL — check ProductVariant\'s existing size values in app/Models/ProductVariant.php or the seeders for the exact size set this store actually sells) plus fit notes (e.g. true-to-size vs relaxed cut) and a how-to-measure-yourself section. Wire it into resources/js/App.jsx routing and add a footer link from Layout.jsx, and link to /size-guide from the FAQ page\'s sizing/fit answer and from ProductDetail.jsx near the size selector. Add every new string to resources/js/i18n/index.js in English and real (non-machine-translated) Hebrew.',
                'agent_name' => 'Creative Agent',
                'task_type' => 'content',
            ],
            [
                'title' => 'Automated production database backups',
                'description' => 'There is no database backup mechanism anywhere in this repo — no backup package in composer.json, no scheduled job, and nothing in .github/workflows/deploy.yml. This is a real production-data-loss risk for a store handling real orders/payments on a single Hostinger MySQL instance with no other persistence layer. Wire up Laravel\'s scheduler for the first time in this project (bootstrap/app.php\'s withSchedule(), Laravel 11+ style — there is currently no Schedule:: usage anywhere) with a daily mysqldump-based backup command that writes a timestamped, rotated (e.g. keep last 14) dump to a directory on the production host outside the git-deployed path (so `git reset --hard` in deploy.yml can never delete a backup), and add the one-time idempotent crontab wiring to deploy.yml\'s existing SSH step so `php artisan schedule:run` actually gets invoked every minute in production (check `crontab -l` for the exact line before appending so redeploys don\'t duplicate it). Since this touches the production SSH deploy pipeline and creates artifacts outside version control, keep the blast radius small: no destructive changes to deploy.yml\'s existing steps, and the backup command must fail loudly (logged SystemEvent or similar) rather than silently if mysqldump is unavailable or credentials are wrong, so a broken backup job is noticed rather than assumed working. Add a feature test for the artisan command\'s rotation/retention logic against SQLite-safe file operations (the mysqldump call itself should be mockable/skippable in the test env).',
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
            'Admin order refund flow',
            'Guest checkout (account-optional purchase flow)',
            'Size guide page',
            'Automated production database backups',
        ])->delete();
    }
};
