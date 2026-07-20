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
                'title' => 'Admin product management silently drops products past page 1',
                'description' => 'Admin\ProductController::index (app/Http/Controllers/Api/Admin/ProductController.php) paginates at 50 per page, but ProductManagement.jsx calls api.get(\'/api/admin/products\') once with no page param, no "load more," and no search box — the exact "silently miss items beyond page 1" bug already fixed for the dashboard admin queues (Dashboard.jsx orders/designs), but never fixed for products. Once the catalog exceeds 50 products, anything created or edited beyond page 1 becomes invisible and uneditable in the admin UI. Add a search/filter query param to the admin index endpoint (mirroring AdminCouponController::index\'s existing search pattern) and wire pagination controls or infinite scroll into ProductManagement.jsx, consistent with how the dashboard queues were already fixed. Feature tests: product #51 is reachable via search or page 2; existing product listing behavior for <=50 products is unchanged.',
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => 'Product JSON-LD never gained the aggregateRating field now that reviews exist',
                'description' => 'ProductDetail.jsx\'s productJsonLd builder has a comment saying structured data has no aggregateRating because "the Product model/API has no review or rating data to draw from... Add it here if/when a Review model ships." Reviews have since shipped: ReviewController::index already returns meta.average_rating/meta.count (app/Http/Controllers/Api/ReviewController.php), and the sibling ProductReviews.jsx component already fetches and displays exactly that data — but productJsonLd in ProductDetail.jsx never includes it, so Google-eligible star-rating rich snippets never render for any product regardless of review count. Lift the reviews-meta fetch (or share it via existing state/context) into ProductDetail.jsx and add aggregateRating: { \'@type\': \'AggregateRating\', ratingValue, reviewCount } to the JSON-LD only when meta.count > 0. Feature/component test: aggregateRating appears in the injected JSON-LD script when a product has reviews and is absent when it has none.',
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => 'Coupon codes have no per-customer redemption cap, only a global one',
                'description' => 'The coupons table and Coupon::isRedeemable()/isExhausted() (app/Models/Coupon.php) only track a single global redemptions_count vs max_redemptions. CouponService\'s validate/lockAndValidate path never checks how many times the current buyer specifically has already redeemed a given code, so a "one per customer" promo can be redeemed unlimited times by the same logged-in user (or repeatedly via fresh guest checkouts) as long as the global cap isn\'t hit. Add an optional max_redemptions_per_user column on coupons, and in CouponService\'s validation count the buyer\'s prior orders with discount_code = $coupon->code and reject at the per-user cap; surface the field in Admin\CouponController\'s validated() rules and CouponManagement.jsx\'s form. Feature tests: same user checking out twice with a max_redemptions_per_user=1 code is blocked on the second attempt while global redemptions_count still has headroom; a code with no per-user cap set behaves exactly as today.',
                'agent_name' => 'Dev Agent',
                'task_type' => 'feature',
            ],
            [
                'title' => 'Admin order refund flow only supports refunding the full order, never a partial amount',
                'description' => 'OrderController::refund (app/Http/Controllers/Api/OrderController.php) hardcodes $payPal->refundCapture($order->paypal_transaction_id) with no amount argument and always flips the whole order to status/payment_status = \'refunded\' — there is no path for a partial/line-item refund (e.g. a customer returns 1 of 2 units, or an admin issues a goodwill partial credit). Accept an optional amount in the refund request, pass it through to PayPalClient::refundCapture, and track a refunded_amount on the order instead of only a binary paid/refunded state, updating OrderStockService::restore so a partial refund only restores stock/coupon usage proportional to what was actually refunded rather than the whole order. Add the partial-refund control to the admin Orders UI. Feature tests: a partial refund leaves payment_status as \'paid\' (or a new \'partially_refunded\' status, whichever fits the existing enum better) and restores only the refunded line item\'s stock quantity, not the full order\'s.',
                'agent_name' => 'Dev Agent',
                'task_type' => 'feature',
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
            'Admin product management silently drops products past page 1',
            'Product JSON-LD never gained the aggregateRating field now that reviews exist',
            'Coupon codes have no per-customer redemption cap, only a global one',
            'Admin order refund flow only supports refunding the full order, never a partial amount',
        ])->delete();
    }
};
