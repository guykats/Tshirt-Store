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
                'title' => 'Registering with a known guest-checkout email silently takes over that account with no ownership proof',
                'description' => "AuthController::register() (app/Http/Controllers/Api/AuthController.php:29-72) implements the 'claim a guest row' flow from the already-shipped 'Checking out as a guest permanently blocks that email from ever registering a real account' task: if the submitted email matches an existing is_guest => true user, it force-fills that row with the submitter's chosen password, flips is_guest to false, then immediately Auth::login()s them (:68) - all with no email verification step (MustVerifyEmail is commented out in app/Models/User.php:5) and no proof the submitter ever placed that guest order. Failure scenario: anyone who simply knows a person's email address (an ex-partner, coworker, or anyone who saw it on a shared package/invoice) can POST /api/register with that email and any password of their choosing, and is instantly logged in as that account - inheriting every prior guest order and saved address on it - with zero verification that they are the original guest. This is a real account-takeover path, not a hypothetical: the exact scenario the claim feature was built to support (an unauthenticated visitor supplying only an email) is also the exact scenario an attacker uses. Fix: require proof of ownership before claiming - e.g. an emailed confirmation link/one-time code sent to the guest email that must be verified before the row is converted, rather than converting synchronously inside the register request.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'security',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => 'Admins have no way to cancel a stuck pending/unpaid order, only refund one that already paid',
                'description' => "OrderPolicy::cancel() (app/Policies/OrderPolicy.php:30) is \$user->id === \$order->user_id only - no isAdmin() branch - while OrderPolicy::refund() (:34) is isAdmin() only, and OrderController::refund() (:239-244) explicitly rejects anything but payment_status === 'paid' ('Only paid orders can be refunded.'). Between those two policies, an order sitting in pending_approval or otherwise unpaid has no admin-reachable action at all: the customer is the only one who can cancel it, and admin staff can't touch it until/unless it gets paid. Failure scenario: a guest or customer abandons checkout after creating an order row (or an order is stuck pending manual approval) and never returns to cancel it themselves - it sits forever with reserved stock and no staff member, including a superadmin, has any API path to cancel it; the admin dashboard's order-management view (Dashboard.jsx) has no cancel action to match because there's no policy path for it to call. Fix: extend OrderPolicy::cancel() to also allow isAdmin(), and add a cancel action to the admin order-management UI for unpaid orders.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => 'Checkout.jsx never handles a failed product fetch — the page stays blank forever instead of showing an error',
                'description' => "resources/js/pages/Checkout.jsx:122-132 - the mount effect calls api.get(\`/api/products/\${productId}\`).then(...) with no .catch(); on success it calls setProduct(res.data.data), but line 147 (if (authLoading || !product) return null;) means product staying null forever (on any network error, 404 for a deleted product, or 500) leaves the entire checkout page rendering nothing - no error message, no retry, no way for the shopper to know anything went wrong. This is the identical silent-failure pattern already fixed on ProductDetail.jsx and Catalog.jsx's product fetches and on Wishlist.jsx/account-addresses, but was missed on this component, which is arguably the highest-stakes place in the app for a shopper to hit a silent dead end (they were already trying to pay). Fix: add .catch() to the product fetch, track a fetch-error state, and render the same retry-capable error UI already established elsewhere instead of returning null.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => 'Admin coupon form accepts a percent value over 100 with no upper bound',
                'description' => "CouponController::validated() (app/Http/Controllers/Api/Admin/CouponController.php:90-98) validates 'value' as ['required', 'numeric', 'min:0'] regardless of 'type', with no conditional max:100 when type === 'percent'. Coupon::discountFor() (app/Models/Coupon.php:61-68) does clamp the resulting discount to the order subtotal (min(max(\$raw, 0), \$subtotal)) so this can't push a total negative, but the coupon row itself still persists and displays a nonsensical value - e.g. an admin fat-fingering '500' instead of '50' creates a coupon that reads as '500% off' everywhere it's shown (admin coupon list, any customer-facing 'X% off' messaging) even though it silently behaves as 100% off at checkout. Fix: add ['required_if:type,percent', 'max:100'] (or an equivalent conditional rule) to the value validation when type is 'percent', so the stored value always matches the actual discount behavior.",
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
        DB::table('project_tasks')->whereIn('title', [
            'Registering with a known guest-checkout email silently takes over that account with no ownership proof',
            'Admins have no way to cancel a stuck pending/unpaid order, only refund one that already paid',
            'Checkout.jsx never handles a failed product fetch — the page stays blank forever instead of showing an error',
            'Admin coupon form accepts a percent value over 100 with no upper bound',
        ])->delete();
    }
};
