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
                'title' => 'Renaming a coupon\'s code in CouponController::update orphans past redemptions, letting redemptions_count drift and wrongly block the renamed code',
                'description' => "app/Http/Controllers/Api/Admin/CouponController.php:59-75 lets an admin freely change a coupon's `code` via update() (only a uniqueness check in validated()). CheckoutController snapshots the code onto the order as `discount_code` at purchase time, and OrderStockService::releaseCoupon (app/Services/OrderStockService.php:77) looks the coupon back up with `Coupon::where('code', \$order->discount_code)` when a cancel/refund needs to give the redemption back. Failure scenario: an admin renames a coupon (e.g. SAVE10 -> SAVE10B) after it has live redemptions; an order placed under the old code is later cancelled or refunded, the lookup by old code returns null, and the release silently no-ops — `redemptions_count` never decrements, so the coupon (now under its new code) can appear artificially exhausted for other customers even though real capacity remains. Fix by freezing `code` from edits once `redemptions_count > 0`, or by releasing via the coupon's `id` recorded on the order instead of the code string.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => 'PayPalWebhookController::markPaid has no row lock, so a duplicate webhook delivery can email the customer two order-confirmation invoices for one payment',
                'description' => "app/Http/Controllers/Api/PayPalWebhookController.php:62-82 reads the order, checks `\$order->payment_status !== 'paid'`, then updates and sends OrderConfirmationMail — with no DB::transaction/lockForUpdate, unlike OrderController::cancel/refund which explicitly lock-and-recheck for exactly this reason. PayPal's webhook system is documented to redeliver the same event on retry/timeout. Failure scenario: PayPal sends PAYMENT.CAPTURE.COMPLETED twice within milliseconds (a known real-world occurrence); both requests read payment_status as not-yet-paid before either commits, so both proceed to Mail::send, generating and emailing the DomPDF invoice twice to the customer. Fix by wrapping the check-then-update in DB::transaction with Order::where(...)->lockForUpdate(), matching the existing cancel/refund pattern.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => 'POST /api/webhooks/paypal has no rate limiting, unlike every other public route, and each request can trigger a real outbound call to PayPal',
                'description' => "routes/api.php:65 registers the PayPal webhook route with no `throttle:*` middleware — every other unauthenticated route in this file (checkout, order-lookup, login, register, reviews, catalog-read, health-check) has an explicit named limiter. PayPalClient::verifyWebhookSignature makes a real synchronous outbound HTTPS call to PayPal's verify-webhook-signature API for every request once services.paypal.webhook_id is configured in production, and doesn't fail fast on garbage input. Failure scenario: once real PayPal credentials are added (owner has deferred this per CLAUDE.md), anyone can flood this unauthenticated, un-throttled endpoint with junk POST bodies, each one forcing the app server to make a blocking outbound call to PayPal, tying up PHP-FPM workers and burning PayPal API quota with no cost to the attacker. Fix by adding a dedicated throttle:paypal-webhook limiter (per-IP, generous but bounded) alongside the existing signature check.",
                'agent_name' => 'Ops Agent',
                'task_type' => 'bug',
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
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    public function down(): void
    {
        DB::table('project_tasks')->whereIn('title', [
            'Renaming a coupon\'s code in CouponController::update orphans past redemptions, letting redemptions_count drift and wrongly block the renamed code',
            'PayPalWebhookController::markPaid has no row lock, so a duplicate webhook delivery can email the customer two order-confirmation invoices for one payment',
            'POST /api/webhooks/paypal has no rate limiting, unlike every other public route, and each request can trigger a real outbound call to PayPal',
        ])->delete();
    }
};
