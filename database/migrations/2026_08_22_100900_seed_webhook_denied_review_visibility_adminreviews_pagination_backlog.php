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
                'title' => 'A late PayPal DENIED webhook can silently flip an already-refunded order back to payment_status "failed"',
                'description' => "app/Http/Controllers/Api/PayPalWebhookController.php:94's markFailed() guards its update with ->where('payment_status', '!=', 'paid') only — it has no guard against an order that has already moved to 'refunded'. PayPal webhooks aren't guaranteed to arrive in order, so a delayed/retried PAYMENT.CAPTURE.DENIED for a capture that was later fully refunded (payment_status='refunded', order status='refunded') can still match the '!= paid' condition and overwrite payment_status back to 'failed', leaving the order's status and payment_status permanently inconsistent with no re-refund possible from that broken state. Fix: exclude 'refunded' (and any other terminal state) from markFailed()'s where clause, mirroring the specificity already used in markPaid().",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => "ReviewController::index has no product-status gate, unlike ProductController::show's abort_unless('active')",
                'description' => "app/Http/Controllers/Api/ReviewController.php:20's public, unauthenticated index() fetches \$product->reviews() with no check that \$product->status === 'active' — contrast ProductController::show (app/Http/Controllers/Api/ProductController.php:54), which explicitly does abort_unless(\$product->status === 'active', 404) before returning anything. Since route model binding resolves {product} by slug (routes/api.php:50) and slugs are derived deterministically from the product name, anyone who guesses or is told a draft/archived product's slug can read every reviewer name, rating, and review body for a product that isn't supposed to be publicly visible yet. Fix: add the same abort_unless('active') gate ReviewController::eligibility and ProductController::show already use.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => 'AdminReviews.jsx strands the admin on an empty page after deleting the last review on a non-first page',
                'description' => "resources/js/pages/AdminReviews.jsx:51's handleDelete() calls loadReviews() (line 56) after a successful delete, but loadReviews() re-requests the same page number already in the URL (line 24's page comes from searchParams, never adjusted). Deleting the only review on the last page (e.g. page 2 of 2) makes the backend's paginator recompute last_page as 1 while current_page in the response still echoes the requested page 2 — the pager controls are gated on meta.last_page > 1 (line 147) so they disappear entirely, leaving the admin on a blank 'no reviews' table with no in-page control to get back to page 1. Fix: after a delete that empties the current page, clamp and navigate back (goToPage(Math.max(1, meta.current_page - 1))) when the reload comes back with fewer pages than the current one.",
                'agent_name' => 'Dev Agent',
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
            'A late PayPal DENIED webhook can silently flip an already-refunded order back to payment_status "failed"',
            "ReviewController::index has no product-status gate, unlike ProductController::show's abort_unless('active')",
            'AdminReviews.jsx strands the admin on an empty page after deleting the last review on a non-first page',
        ])->delete();
    }
};
