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
                'title' => "Declined PayPal payments never write a SystemEvent, unlike every other order-status mutation",
                'description' => "Both code paths that set an order's payment_status to 'failed' - PayPalWebhookController::markFailed() (app/Http/Controllers/Api/PayPalWebhookController.php:84-96, triggered by a PAYMENT.CAPTURE.DENIED webhook) and CheckoutController::capture() (app/Http/Controllers/Api/CheckoutController.php:264-269, triggered when PayPal returns a non-COMPLETED status to the buyer's own capture call) - skip SystemEvent::log() entirely, while order.paid, order.approved, order.cancelled, order.refunded, order.status_advanced, and order.expired all log one. An admin auditing why a customer's payment failed has no audit-log trace of it at all. markFailed() also does a raw Order::where(...)->update(...) mass update, bypassing Eloquent model events. Fix: add SystemEvent::log('order.payment_failed', ...) at both call sites, and add the new event type to AuditLog.jsx's event-type filter.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "Four more admin dashboard pages render timestamps in fixed English formatting regardless of site language",
                'description' => "resources/js/pages/ProjectProgress.jsx:161, resources/js/pages/Dashboard.jsx:392 and 409, resources/js/pages/AuditLog.jsx:236, and resources/js/pages/AdminReviews.jsx:109 all call new Date(...).toLocaleDateString() / .toLocaleString() with no locale argument, so task timestamps, system-event timestamps, deploy-commit dates, and review timestamps stay in English date formatting even when an admin is using the Hebrew UI - the same underlying bug already flagged for OrderCard.jsx and CouponManagement.jsx's expiry column, but present in four additional admin-panel files not covered by either of those tasks. Fix: pass i18n.language into a locale-aware date formatter at each of these five call sites, mirroring the app's existing formatPrice pattern.",
                'agent_name' => 'Creative Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "Saved-address CRUD has no rate limiting, unlike every comparable authenticated write route",
                'description' => "The /account/addresses routes (routes/api.php:92-96 - POST, PUT /{address}, DELETE /{address}, POST /{address}/default, handled by AddressController) carry no throttle: middleware at all, while AppServiceProvider::boot() defines named limiters for login, register, checkout, reviews, visioner-chat, and account-security (change-password/delete-account - app/Providers/AppServiceProvider.php:26-105). An authenticated attacker or buggy client script can create, edit, delete, or reset the default flag on address rows with no limit, structurally the same gap already flagged for the wishlist endpoints but in a separate, unflagged route family. Fix: add a named rate limiter (e.g. 'account-write', perMinute by user id) and apply throttle: middleware to the address routes.",
                'agent_name' => 'Ops Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "Catalog and product-detail loading skeletons give screen-reader users no loading announcement",
                'description' => "resources/js/components/RouteLoading.jsx was deliberately built with role=\"status\" aria-live=\"polite\" so screen-reader users get an announced state during route-chunk loading. CatalogSkeleton and ProductDetailSkeleton (resources/js/components/Skeleton.jsx, used at resources/js/pages/Catalog.jsx:255 and resources/js/pages/ProductDetail.jsx:132) - shown on the two highest-traffic customer pages while product data fetches - carry no ARIA attributes at all and aren't wrapped in a status region, so a screen-reader user gets total silence during the fetch and no announcement when real content replaces the skeleton. Fix: wrap both skeletons in a role=\"status\" aria-live=\"polite\" container with a visually-hidden loading label, mirroring RouteLoading.jsx's existing pattern.",
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
        DB::table('project_tasks')->where('title', "Declined PayPal payments never write a SystemEvent, unlike every other order-status mutation")->delete();
        DB::table('project_tasks')->where('title', "Four more admin dashboard pages render timestamps in fixed English formatting regardless of site language")->delete();
        DB::table('project_tasks')->where('title', "Saved-address CRUD has no rate limiting, unlike every comparable authenticated write route")->delete();
        DB::table('project_tasks')->where('title', "Catalog and product-detail loading skeletons give screen-reader users no loading announcement")->delete();
    }
};
