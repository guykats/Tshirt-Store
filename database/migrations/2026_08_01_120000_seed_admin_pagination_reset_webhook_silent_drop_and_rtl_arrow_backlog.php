<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('project_tasks')->insert([
            [
                'epic_id' => null,
                'title' => 'Deleting the last row on an admin list page leaves the admin stuck looking at a blank page',
                'description' => "AdminReviews.jsx's handleDelete() (resources/js/pages/AdminReviews.jsx:50-58) and ProductManagement.jsx's handleProductDelete() (resources/js/pages/ProductManagement.jsx:169-177) both delete the row, then call loadReviews()/loadProducts() again with the same 'page' value read from useSearchParams (AdminReviews.jsx:23, ProductManagement.jsx:49) -- neither ever adjusts or clears the page param after a delete. Laravel's ->paginate() on the backing admin endpoints (ReviewController::manage, Admin\\ProductController::index) just returns an empty 'data' array for a page number past the new last page once the row count drops. Failure scenario: an admin viewing the single remaining review (or product) on page 2 deletes it; the list reloads for page=2, which now returns zero rows, and the admin sees an empty 'no reviews found'/'no products found' table with no indication that pages 1 sill has data -- they have to manually click Previous or edit the URL to recover, and might assume the whole list emptied out. Fix: after a delete succeeds, if the reloaded page comes back empty and page > 1, step back a page (or just always reload page 1) before re-fetching.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => 'PayPalWebhookController::markPaid silently drops a completed-capture webhook when no matching order exists, with zero logging',
                'description' => "markPaid() in app/Http/Controllers/Api/PayPalWebhookController.php:50-77 does \$order = Order::where('paypal_order_id', \$paypalOrderId)->first(); then only acts 'if (\$order && ...)' (line 62) -- when \$order is null there is no Log::warning, no SystemEvent::log, nothing; the method just falls through and returns, and handle() (line 46) still responds 200 {\"status\":\"ok\"} to PayPal so PayPal never retries either. Failure scenario: PayPal sends PAYMENT.CAPTURE.COMPLETED for a paypal_order_id that doesn't match any local Order -- a stale/duplicate delivery after data drift, a replayed sandbox event, or a race where the order row was somehow removed -- and the payment confirmation vanishes without a trace: no audit-log entry, no warning log, nothing for an admin to find even by searching the System Events feed. This is inconsistent with the sibling markFailed() path and every other payment-failure path in the codebase, which do log. Fix: log a warning (and/or write a SystemEvent) when \$order is null, mirroring how signature-verification failures are already logged in handle().",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "ProductDetail.jsx's 'back to catalog' link uses a hardcoded left-arrow glyph that doesn't mirror for Hebrew/RTL",
                'description' => "ProductDetail.jsx:144 renders '&larr; {t('catalog_title')}' -- a literal Unicode leftward-arrow entity, not a directional/logical icon. RTL is applied document-wide via Layout.jsx:16 (document.documentElement.dir = next === 'he' ? 'rtl' : 'ltr'), and bidi reordering only reorders inline text runs -- it never rotates a fixed glyph like &larr;. A grep across resources/js confirms this is the only such literal arrow in the app (every other list/back affordance uses layout, not a hardcoded arrow character). Failure scenario: a Hebrew-locale shopper on a product page sees '← קטלוג' where the arrow still visually points left even though the page's 'start' edge (where a back affordance belongs) is now the right side of the screen in RTL -- the arrow either sits on the wrong side of the text or points the wrong direction for 'back', unlike the rest of the app which is built to genuinely mirror per CLAUDE.md's bilingual convention. Fix: use a CSS logical-property-aware icon (e.g. an inline SVG that flips via `rtl:rotate-180` / `dir`-aware transform, matching however other directional icons in the codebase already handle this) instead of a fixed HTML entity.",
                'agent_name' => 'Creative Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')->whereIn('title', [
            'Deleting the last row on an admin list page leaves the admin stuck looking at a blank page',
            'PayPalWebhookController::markPaid silently drops a completed-capture webhook when no matching order exists, with zero logging',
            "ProductDetail.jsx's 'back to catalog' link uses a hardcoded left-arrow glyph that doesn't mirror for Hebrew/RTL",
        ])->delete();
    }
};
