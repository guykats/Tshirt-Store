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
                'title' => "Public product endpoints leak internal Design admin metadata, including the reviewing admin's name and email",
                'description' => "ProductController::index/show (app/Http/Controllers/Api/ProductController.php) eager-load 'design' with zero gating and serialize it through DesignResource on the fully public /api/products and /api/products/{product} routes. DesignResource unconditionally includes rejection_reason, source_agent, the internal moderation status, and approved_by — a nested UserResource that exposes the reviewing admin's real name, email, phone, and role. Any anonymous visitor hitting a product page's network tab can currently read which staff member approved a design and their email address, none of which has any business being public. Fix: add a public-facing DesignResource variant (or a whitelist inside the existing one) that only exposes title/description/category/mockup_url when serialized for the unauthenticated catalog, keeping the full admin fields for the authenticated Admin\\DesignController responses only.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "Renaming a coupon's code permanently strands the redemption count of every order that already used the old code",
                'description' => "OrderStockService::releaseCoupon() (app/Services/OrderStockService.php) looks up the coupon to credit back via Coupon::where('code', \$order->discount_code)->lockForUpdate()->first() — the literal discount_code text frozen onto the order at checkout. CouponController::update() lets an admin rename a coupon's code with no restriction. Once renamed, cancelling or refunding any order placed under the old code finds no matching coupon row, silently returns, and never decrements redemptions_count — permanently inflating it and potentially exhausting a coupon's redemption cap early even though real capacity was freed up. This is the opposite failure mode from the known per-customer-cap reset on rename (task tracking the cap resetting too leniently); here the global count gets stuck too high. Fix: look up the coupon by an immutable identifier (e.g. a coupon_id column on orders captured at checkout) instead of the mutable code string.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "Marking an order shipped with an unrecognized carrier name silently drops the tracking link with no warning to the admin",
                'description' => "OrderController::advanceStatus() (app/Http/Controllers/Api/OrderController.php) validates 'carrier' only as a free-form string (required|string|max:100), but CarrierTracking::url() (app/Services/CarrierTracking.php) only recognizes 4 hardcoded names (usps, ups, fedex, israel post) and returns null for anything else, including typos or differently-named couriers (e.g. 'DHL', 'Israel Post' with different capitalization already works via strtolower but 'IL Post' wouldn't). The order still saves as shipped with the carrier text stored fine, but the tracking link silently disappears from the shipped email, the invoice, and the customer's order page — the admin who typed it gets no error or confirmation that the link wasn't generated. Fix: surface a warning in the admin UI when the entered carrier doesn't match a known pattern, so the gap is visible at entry time instead of only showing up as a missing link days later.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "Low-stock and backup-failure admin alert emails ignore the recipient admin's preferred_locale, unlike every other transactional notification",
                'description' => "App\\Notifications\\LowStockAlert and App\\Notifications\\BackupFailed are both dispatched via Notification::send(User::where('role', 'admin')->get(), ...) (in CheckoutController::store and BackupDatabase respectively) with no ->locale() call anywhere in the chain. Every other locale-aware notification in the app — ResetPasswordNotification (App\\Models\\User::sendPasswordResetNotification()) and all order Mailables (OrderConfirmationMail, OrderShippedMail, OrderDeliveredMail, OrderRefundedMail) — explicitly calls ->locale(\$user->preferred_locale ?? 'en') before sending. A Hebrew-preferring admin currently gets low-stock and backup-failure alerts rendered in whichever locale happens to be globally active at send time (typically English, since these fire from queued jobs/console commands with no request-scoped locale), not their own preference. Fix: wrap both Notification::send() calls to set each admin's locale individually (Notification::send() doesn't support a single shared ->locale() the way Mail::to() does when recipients can have different locales, so this needs a per-user loop or NotificationFake-compatible per-notifiable locale resolution).",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "Rejecting a Design after it's already been built into a live Product leaves that Product fully active with no cascading effect or admin signal",
                'description' => "DesignController::reject() (app/Http/Controllers/Api/DesignController.php) only ever updates the Design row's status/rejection_reason/approved_by — it never checks whether any Product already has design_id pointing at it. Because AdminProductController::store()/update() only validates that a design_id exists (exists:designs,id), not that its status is 'approved' at the time of use, a design can legitimately be approved, built into a published product, sell for a while, and only later get rejected (e.g. a retroactive trademark/content concern). When that happens today, the already-published product stays status: active and fully purchasable with zero indication anywhere in the admin UI that its underlying design is no longer approved. This is a distinct gap from the existing 'admin product create/update accepts a pending or rejected design's id with no server-side status check' issue, which is about validation at creation time, not about what happens to products that were built on a design that gets rejected afterward. Fix: when a Design transitions to rejected, flag or surface any Product still referencing it (at minimum a warning badge in the admin product list; ideally an admin-facing prompt to deactivate the product).",
                'agent_name' => 'Dev Agent',
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
        DB::table('project_tasks')->where('title', "Public product endpoints leak internal Design admin metadata, including the reviewing admin's name and email")->delete();
        DB::table('project_tasks')->where('title', "Renaming a coupon's code permanently strands the redemption count of every order that already used the old code")->delete();
        DB::table('project_tasks')->where('title', "Marking an order shipped with an unrecognized carrier name silently drops the tracking link with no warning to the admin")->delete();
        DB::table('project_tasks')->where('title', "Low-stock and backup-failure admin alert emails ignore the recipient admin's preferred_locale, unlike every other transactional notification")->delete();
        DB::table('project_tasks')->where('title', "Rejecting a Design after it's already been built into a live Product leaves that Product fully active with no cascading effect or admin signal")->delete();
    }
};
