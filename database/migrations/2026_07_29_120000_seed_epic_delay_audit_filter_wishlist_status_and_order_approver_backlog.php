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
                'title' => "EpicController::delay() has no status guard and can silently revert an already-approved epic back to proposed",
                'description' => "EpicController::delay() (app/Http/Controllers/Api/EpicController.php:71-89) sets \$epic->update(['status' => 'proposed', 'priority' => \$backOfTheLine]) unconditionally, with no check on the epic's current status — unlike approve()/reject(), which are meant to be the only ways in or out of 'approved'. Failure scenario: an epic is approved (project_tasks may already be spawned under its epic_id), then an admin double-clicks or replays a stale 'Delay' request (e.g. from a second browser tab still showing the old list) against that same epic id — it silently flips back to 'proposed' with no error, even though tasks already exist under it, undoing the approval decision with no trace beyond the epic.delayed audit log entry. Fix: abort_unless the epic's current status is 'proposed' before applying delay(), matching the implicit state-machine the UI assumes.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "AuditLog.jsx's event-type filter is missing 8 event types the backend actually emits",
                'description' => "AuditLog.jsx's hardcoded EVENT_TYPES list (resources/js/pages/AuditLog.jsx:12-42) is a manually-maintained mirror of every SystemEvent::log() call's first argument across the backend, per its own comment. It's out of date: grepping every SystemEvent::log( call site backend-wide turns up 37 distinct event_type strings, but the frontend list only has 29 — missing 'coupon.created', 'coupon.updated' (app/Http/Controllers/Api/Admin/CouponController.php), 'order.expired' (app/Console/Commands/ExpireAbandonedOrders.php), 'review.deleted' (app/Http/Controllers/Api/ReviewController.php), 'user.guest_claimed', 'user.self_deleted' (app/Http/Controllers/Api/AuthController.php), and 'backup.offsite_uploaded', 'backup.offsite_failed' (app/Console/Commands/BackupDatabase.php). An admin trying to filter the audit log down to, say, self-service account deletions or offsite-backup failures has no dropdown option to do it — they have to scroll the unfiltered feed. Fix: add the 8 missing strings to EVENT_TYPES, and consider a lightweight test asserting the list stays in sync with a repo-wide grep of SystemEvent::log( call sites so this doesn't silently drift again.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "Wishlist.jsx renders an admin-archived product as a normal clickable item that 404s when opened",
                'description' => "WishlistController::index() (app/Http/Controllers/Api/WishlistController.php:17-28) returns every saved wishlist item's product with no status filter, and Wishlist.jsx (resources/js/pages/Wishlist.jsx:43-56) renders each one as a plain <Link to={/products/{item.product.slug}}> with no check on item.product.status. ProductController::show() (app/Http/Controllers/Api/ProductController.php:54) does abort_unless(\$product->status === 'active', 404) — so once an admin drafts or archives a product, every customer with it wishlisted keeps seeing it in their wishlist looking normal and purchasable, and hits a bare 404 the moment they click through. Distinct from the already-tracked GarmentMockup/no-.catch()/error-state tickets, none of which cover stale-status wishlist entries. Fix: either exclude non-active products from the wishlist index response, or render them with a visibly disabled/unavailable state instead of a live link, consistent with how the catalog already hides non-active products entirely.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "OrderResource's approved_by field is always empty because OrderController never eager-loads the approvedBy relation",
                'description' => "Order::approvedBy() (app/Models/Order.php:49-52) and OrderResource's 'approved_by' => new UserResource(\$this->whenLoaded('approvedBy')) (app/Http/Resources/OrderResource.php:29) are both wired correctly, and OrderController::approve() does persist approved_by (app/Http/Controllers/Api/OrderController.php:111). But none of OrderController's read paths ever load that relation: lookup() (:42), index() (:68), and show() (:98) all eager-load only ['user', ...] variants, never 'approvedBy' — unlike DesignController, which does ->with('approvedBy') on its index query and ->fresh('approvedBy') after mutating (app/Http/Controllers/Api/DesignController.php:19,45,71), the exact pattern this file should mirror. Net effect: whenLoaded() always returns a MissingValue, so approved_by is silently omitted from every order API response and no UI can ever show which admin approved an order, even though the data has been recorded correctly since the order-approval feature shipped. Fix: add 'approvedBy' to the eager-load lists in lookup(), index(), and show(), matching DesignController's pattern.",
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
        DB::table('project_tasks')->where('title', "EpicController::delay() has no status guard and can silently revert an already-approved epic back to proposed")->delete();
        DB::table('project_tasks')->where('title', "AuditLog.jsx's event-type filter is missing 8 event types the backend actually emits")->delete();
        DB::table('project_tasks')->where('title', "Wishlist.jsx renders an admin-archived product as a normal clickable item that 404s when opened")->delete();
        DB::table('project_tasks')->where('title', "OrderResource's approved_by field is always empty because OrderController never eager-loads the approvedBy relation")->delete();
    }
};
