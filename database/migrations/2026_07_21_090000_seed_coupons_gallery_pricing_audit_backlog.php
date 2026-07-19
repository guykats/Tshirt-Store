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
                'title' => 'Discount/coupon codes at checkout',
                'description' => 'CheckoutController::store (app/Http/Controllers/Api/CheckoutController.php) computes subtotal/total_amount purely from unit_price * quantity with no discount concept anywhere — the orders table has no discount_code/discount_amount column, and Checkout.jsx (resources/js/pages/Checkout.jsx) has no promo-code input field. Add a coupons table (code, type [percent|fixed], value, expires_at, max_redemptions, active) plus a nullable discount_code/discount_amount pair on orders, a CouponService that validates and computes the discount inside CheckoutController::store\'s existing DB::transaction (recomputing subtotal/total_amount), expose it as a "code" field the shopper enters on Checkout.jsx before submitting, and surface the applied discount on OrderResource and the PDF invoice (InvoiceService). Write feature tests for: valid code reduces total_amount correctly, expired/exhausted/unknown code is rejected with a 422, and an order\'s stored discount_amount survives refund/cancellation flows unchanged.',
                'agent_name' => 'Dev Agent',
                'task_type' => 'feature',
            ],
            [
                'title' => 'Product image gallery (multiple images per product)',
                'description' => 'Every product currently renders exactly one visual — Design::mockup_url (app/Models/Design.php) is passed as `motif` into a single <GarmentMockup> in ProductDetail.jsx, with no gallery, thumbnails, or per-variant-color images at all; ProductResource (app/Http/Resources/ProductResource.php) only ever nests the one DesignResource. Add a product_images table (product_id, url, position, alt_text, optionally variant color scoping) with an admin CRUD surface in ProductManagement.jsx / the Admin product controller to upload/reorder/delete images, expose the ordered list on ProductResource, and replace ProductDetail.jsx\'s single GarmentMockup with a thumbnail strip/carousel that switches the main image on click and reacts to the selected color like GarmentMockup already does for the mockup art. Write feature tests for the admin image CRUD endpoints (ordering, deletion, product-not-found) and a frontend test asserting the carousel renders all images and updates the main image on thumbnail click.',
                'agent_name' => 'Dev Agent',
                'task_type' => 'feature',
            ],
            [
                'title' => 'Locale-aware price and number formatting for Hebrew',
                'description' => 'Every price in the app is built by hand-concatenating `{product.currency} {base_price.toFixed(2)}` (found in resources/js/pages/ProductDetail.jsx, Catalog.jsx, Checkout.jsx, Dashboard.jsx, Orders.jsx, Wishlist.jsx) with zero use of Intl.NumberFormat anywhere in resources/js — so a Hebrew-locale shopper (the app already fully supports an he/RTL toggle in Layout.jsx) sees the exact same "USD 19.99"-style string as an English shopper instead of locale-correct grouping/decimal punctuation. Add a shared formatPrice(amount, currency, locale) helper (e.g. resources/js/lib/formatPrice.js) built on Intl.NumberFormat(i18n.language, { style: \'currency\', currency }), replace every one of the toFixed(2) call sites above with it, and confirm it renders sensibly for both \'en\' and \'he\' locales including RTL number/currency ordering. Add a Playwright screenshot showing the same product priced in both locales, plus a Vitest unit test for formatPrice covering en and he locale output.',
                'agent_name' => 'Creative Agent',
                'task_type' => 'content',
            ],
            [
                'title' => 'Admin audit-log page with filtering and pagination',
                'description' => 'SystemEventController::index (app/Http/Controllers/Api/SystemEventController.php) already paginates system_events 30-at-a-time and a real audit trail is logged (SystemEvent::log calls scattered across OrderController, CheckoutController, DesignController, etc.), but Dashboard.jsx just calls `api.get(\'/api/system-events\')` once on mount, discards the pagination meta, and dumps page 1\'s 30 rows into a fixed max-h-96 scrolling `<ul>` — there is no way for an admin to see event 31+, filter by event_type or actor_type, or search by order/product id. Add query-param filtering (event_type, actor_type, date range) to SystemEventController::index, and build a dedicated /admin/audit-log route/page that paginates through results using the existing meta.last_page rather than the current scroll-and-forget widget, with filter dropdowns/inputs wired to those new query params. Write feature tests for SystemEventController::index filtering (by event_type, by actor_type, combined) and pagination beyond page 1, non-admin still gets 403.',
                'agent_name' => 'Ops Agent',
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
            'Discount/coupon codes at checkout',
            'Product image gallery (multiple images per product)',
            'Locale-aware price and number formatting for Hebrew',
            'Admin audit-log page with filtering and pagination',
        ])->delete();
    }
};
