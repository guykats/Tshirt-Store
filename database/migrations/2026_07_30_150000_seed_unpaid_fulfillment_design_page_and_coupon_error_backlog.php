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
                'title' => 'advanceStatus() and the Dashboard fulfillment queue never check payment_status, so an unpaid order can be walked all the way to delivered',
                'description' => "OrderController::advanceStatus() (app/Http/Controllers/Api/OrderController.php, line 135) moves an order through Order::STATUS_SEQUENCE (pending_approval -> approved -> processing -> shipped -> delivered) purely off Order::nextFulfillmentStatus(), with no check anywhere on payment_status -- unlike cancel() and refund() in the same controller, which both explicitly guard on payment_status. Dashboard.jsx's loadFulfillmentOrders() (resources/js/pages/Dashboard.jsx, lines 70-74) mirrors the same gap on the client: fulfillmentOrders is filtered only by order.status in NEXT_FULFILLMENT_STATUS, while the very next line building refundableOrders explicitly adds .filter(order => order.payment_status === 'paid'). Together this means an order whose PayPal capture never completed (or failed) still shows up in the admin fulfillment queue and can be clicked all the way to 'delivered', including triggering the real OrderShippedMail/OrderDeliveredMail sends, without ever collecting payment. Fix: add a payment_status === 'paid' guard to advanceStatus() (mirroring cancel()/refund()) and to the Dashboard fulfillment-queue filter.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "ProductManagement.jsx's Design picker only ever fetches page 1 of approved designs, silently hiding the 21st and later ones",
                'description' => "ProductManagement.jsx's design-loading effect (resources/js/pages/ProductManagement.jsx, lines 77-80) calls api.get('/api/designs', { params: { status: 'approved' } }) directly and does setDesigns(res.data.data) with no pagination handling, but DesignController::index() (app/Http/Controllers/Api/DesignController.php, line 13-22) paginates results 20-per-page. The same file already has a fetchAllPages helper in active use elsewhere for exactly this reason (see the admin orders/pending-designs loaders in Dashboard.jsx and ProductManagement's own product list), but the Design picker for product creation/editing doesn't use it. Once a store accumulates more than 20 approved designs, the create/edit-product Design dropdown silently can't select the 21st+ one -- there's no error, no 'load more', it just isn't in the list. Fix: swap the raw api.get call for fetchAllPages('/api/designs', { status: 'approved' }), matching the pattern already used for orders/products elsewhere in this codebase.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => 'Checkout only translates 1 of CouponService\'s 4 rejection messages, so most invalid-coupon errors render as raw English inside the Hebrew checkout form',
                'description' => "Checkout.jsx's KNOWN_BACKEND_ERROR_KEYS (resources/js/pages/Checkout.jsx, lines 17-19) maps exactly one backend coupon-rejection string ('You have already used this coupon the maximum number of times allowed.') to an i18n key, with translateCheckoutError() (lines 21-25) falling back to the raw, untranslated message for anything else. But CouponService::validate() (app/Services/CouponService.php, lines 34-50) throws four distinct InvalidCouponException messages: two 'This coupon code is not valid.' cases, 'This coupon code has expired.', and 'This coupon code has already been fully redeemed.' -- none of those three are in the map, so a Hebrew-locale shopper who enters an expired, already-maxed-out, or unknown coupon code sees a raw English sentence dropped into an otherwise fully-Hebrew checkout form. Fix: add the three missing messages to KNOWN_BACKEND_ERROR_KEYS with real Hebrew i18n entries, matching the pattern already used for the per-customer-cap message.",
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
        DB::table('project_tasks')->where('title', 'advanceStatus() and the Dashboard fulfillment queue never check payment_status, so an unpaid order can be walked all the way to delivered')->delete();
        DB::table('project_tasks')->where('title', "ProductManagement.jsx's Design picker only ever fetches page 1 of approved designs, silently hiding the 21st and later ones")->delete();
        DB::table('project_tasks')->where('title', "Checkout only translates 1 of CouponService's 4 rejection messages, so most invalid-coupon errors render as raw English inside the Hebrew checkout form")->delete();
    }
};
