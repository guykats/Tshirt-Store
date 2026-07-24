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
                'title' => "VisionerChatController::store persists the user's message before calling Anthropic, orphaning it and duplicating it on retry after a failure",
                'description' => "VisionerChatController::store (app/Http/Controllers/Api/VisionerChatController.php:24-38) creates the VisionerChatMessage row for the admin's prompt, then calls \$anthropic->converse(...). AnthropicClient::send() (app/Services/AnthropicClient.php:85-98) wraps the HTTP call in try/catch but only translates a Guzzle RequestException into a bare RuntimeException - any API failure (rate limit, timeout, bad key) propagates uncaught out of the controller as an unhandled 500, with the user's message already committed and no assistant reply ever attached. VisionerChat.jsx's catch handler (resources/js/pages/VisionerChat.jsx:39-41) restores the typed draft so the admin naturally resubmits, creating a second, duplicate user message alongside the first orphaned one. Fix: wrap the converse() call in a try/catch in the controller, and either delete the orphaned user message or attach a visible error-role message on failure.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "Admin product create/update accepts a pending or rejected Design's id with no server-side status check",
                'description' => "Admin\\ProductController's validation (app/Http/Controllers/Api/Admin/ProductController.php:125) only checks 'design_id' => ['required', 'integer', 'exists:designs,id'] - it never constrains the referenced Design's status. The only place that filters to approved designs is client-side, ProductManagement.jsx fetching /api/designs?status=approved for the dropdown. A direct API call (or a future UI regression) can attach a still-pending or explicitly-rejected Design straight onto a live, active product, or swap an existing product's design to one later rejected, completely bypassing the Design approve/reject workflow at the one point it's meant to matter. Fix: add a status-aware validation rule (e.g. Rule::exists('designs', 'id')->where('status', 'approved')) to both the create and update validation in Admin\\ProductController.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "A review stays live and keeps counting toward a product's rating after its backing order is refunded or cancelled",
                'description' => "OrderController::cancel() and refund() (app/Http/Controllers/Api/OrderController.php:193-296) never touch the reviews table, and ReviewController has no listener on order-status changes. ReviewController::eligibility only blocks writing a *new* review once an order is no longer payment_status === 'paid' (app/Http/Controllers/Api/ReviewController.php:186), but a review already submitted while the order was paid stays fully visible after a later refund/cancel, and keeps counting toward ProductController's withAvg('reviews','rating')/withCount('reviews') used on the catalog card, the product page, and the JSON-LD aggregateRating. A refunded 'customer' keeps social-proof credit indefinitely. Fix: either soft-hide reviews whose order is no longer paid from the aggregate/display queries, or re-verify review eligibility against current order status on a schedule/webhook.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "Admin order search and the audit-log search still don't escape SQL LIKE wildcards, beyond the coupon/product pair already fixed",
                'description' => "A prior fix escaped LIKE wildcards in Admin\\CouponController::index and Admin\\ProductController::index, but two more admin-facing search endpoints have the identical defect and are untouched by that fix: OrderController::index (app/Http/Controllers/Api/OrderController.php:79-86, \$like = '%'.strtolower(\$search).'%' matched against customer name/email/order_number) and SystemEventController::index (app/Http/Controllers/Api/SystemEventController.php:23, '%'.\$search.'%' matched against description). An admin searching orders or the audit log for a literal underscore (common in emails, e.g. first_last@example.com) gets it silently treated as a SQL single-character wildcard, returning unrelated rows instead of the exact match typed. Fix: apply the same escape-before-LIKE helper to both of these search methods.",
                'agent_name' => 'Ops Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "No gated action passes a return-to path when bouncing a logged-out visitor to /login, so signing in never returns them to where they were",
                'description' => "Every redirect-to-login site-wide is a bare, contextless navigation with no return path: WishlistButton.jsx:25 (navigate('/login') when saving a product while logged out), Checkout.jsx:206, and the route guards in App.jsx:50/59 (<Navigate to=\"/login\" replace /> for /account, /orders, /wishlist, /dashboard). None pass a ?redirect= query param or router state, and Login.jsx always sends a successful login to a fixed destination regardless of where the visitor came from. A shopper who clicks the wishlist heart on a product page while logged out, or who is bounced off /orders, is unconditionally dropped at the fixed destination after signing in instead of back on the page they were trying to reach. Fix: have login-redirecting call sites pass the current path (state or ?redirect=), and have Login.jsx honor it after a successful sign-in, falling back to the current default only when none is present.",
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
        DB::table('project_tasks')->where('title', "VisionerChatController::store persists the user's message before calling Anthropic, orphaning it and duplicating it on retry after a failure")->delete();
        DB::table('project_tasks')->where('title', "Admin product create/update accepts a pending or rejected Design's id with no server-side status check")->delete();
        DB::table('project_tasks')->where('title', "A review stays live and keeps counting toward a product's rating after its backing order is refunded or cancelled")->delete();
        DB::table('project_tasks')->where('title', "Admin order search and the audit-log search still don't escape SQL LIKE wildcards, beyond the coupon/product pair already fixed")->delete();
        DB::table('project_tasks')->where('title', "No gated action passes a return-to path when bouncing a logged-out visitor to /login, so signing in never returns them to where they were")->delete();
    }
};
