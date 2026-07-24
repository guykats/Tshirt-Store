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
                'title' => 'A declined or failed PayPal capture never releases the stock or coupon redemption it reserved',
                'description' => "CheckoutController::store() reserves stock and increments a coupon's redemptions_count inside a locked transaction at order-creation time, before payment happens (app/Http/Controllers/Api/CheckoutController.php, around lines 147-175). OrderStockService::restore() is the only code path that gives that reservation back, and it is wired into exactly three places: OrderController::cancel() (line 214), OrderController::refund() (line 274), and ExpireAbandonedOrders (line 55). CheckoutController::capture() sets payment_status to 'failed' on a non-COMPLETED PayPal status (line 267) and PayPalWebhookController::markFailed() does the same for a DENIED webhook -- neither calls restore(). Concrete scenario: a shopper's card is declined on the last remaining unit of a size/color combo. The system already knows definitively that sale will never complete, yet that unit stays reserved -- invisible to other shoppers as sold out, and excluded from the coupon's redemption cap for anyone else -- until ExpireAbandonedOrders sweeps it up to config('checkout.reservation_minutes') (default 60) minutes later, or the buyer manually cancels. Fix: call \$this->stock->restore(\$order) (inside a locked transaction, the same pattern cancel() already uses) from both failure paths.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "Admin variant edit is an unlocked lost-update race that can silently un-sell stock a customer already paid for",
                'description' => "CheckoutController::store() reserves stock safely: it row-locks the variant inside a transaction and decrements it (ProductVariant::where('id', \$variant->id)->lockForUpdate()->firstOrFail() then ->decrement(...), app/Http/Controllers/Api/CheckoutController.php around lines 147-153). Admin\\ProductVariantController::update() (app/Http/Controllers/Api/Admin/ProductVariantController.php, lines 40-69) has no such guard -- it does a plain, unlocked \$variant->update(\$data) where \$data['stock_quantity'] is whatever absolute number was loaded into the admin edit form, not a delta. Concrete failure: an admin opens the edit modal for a variant showing stock_quantity: 10. While that form sits open, a customer completes checkout for 2 units, correctly decrementing it to 8 and getting charged. The admin then submits the form (perhaps only changing the price) still carrying the stale stock_quantity: 10, and the unlocked update() overwrites the row back to 10 -- silently erasing the two units the customer legitimately paid for and reintroducing phantom stock that can now be oversold to someone else. Fix: guard the stock_quantity write with lockForUpdate() or an optimistic-concurrency check (e.g. compare against a stale updated_at), or change the admin form to submit a delta rather than resubmitting an absolute snapshot, mirroring how CheckoutController and OrderStockService already treat stock writes as a locked critical section.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "Editing a saved address after it's been used on an order retroactively rewrites that order's shipping and billing address everywhere",
                'description' => "AddressController::destroy() explicitly blocks deleting an address still referenced by an order (app/Http/Controllers/Api/AddressController.php, lines 97-110), but AddressController::update() (lines 79-88) has no equivalent guard -- it does a plain \$address->update(\$data) regardless of order history. Every place that renders an order's address reads the live relation, not a snapshot: OrderResource builds shipping_address/billing_address from the loaded shippingAddress/billingAddress relations, and invoice generation and order emails both load those relations fresh on every call. Concrete scenario: a customer edits a saved address in Account Settings months later -- to fix a typo or because they moved -- and every one of their past orders, including already-issued invoices re-downloaded later and what an admin sees in the fulfillment/refund views, now shows the new address as if that's where the order was actually shipped, corrupting the historical shipping and tax record. This is the same missing-snapshot root cause already tracked for variant size/color and product name (see the existing 'retroactively rewrites every past order's displayed details' task), but that item is scoped only to product_variants and products -- addresses is a separate table with no snapshot mechanism of its own and needs its own fix. Fix: snapshot the address fields onto the order at checkout time, or block editing an address once it's referenced by an order the same way destroy() already does.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => 'Admin variant create/update has a duplicate-combo TOCTOU race that surfaces a raw 500 instead of the clean 422 it was designed to produce',
                'description' => "Admin\\ProductVariantController::store()/update() (app/Http/Controllers/Api/Admin/ProductVariantController.php) guard against a duplicate (product_id, size, color) combo with a pre-check duplicateCombo() query, then insert or update -- with no try/catch around the actual create()/update() call. The controller's own doc comment on duplicateCombo() already acknowledges the DB's unique constraint would catch this too, 'but as a raw constraint-violation 500 rather than a clean 422' -- yet nothing actually catches that constraint violation if two writes race past the pre-check. Every other place in the app with the identical exists-then-insert shape does catch this race: WishlistController::store() wraps its insert in try/catch (QueryException \$e), and ReviewController::store() does the same. Concrete scenario: two admins (or one admin double-clicking, or two open tabs) submit a new S/Black variant for the same product within the same request window -- both pass duplicateCombo()'s pre-check since neither has committed yet, the second insert then hits the DB's unique(product_id, size, color) constraint and throws an uncaught QueryException, returning a raw 500 to the admin instead of the intended 'A variant with that size and color already exists' 422. Fix: wrap the create()/update() calls in the same catch (QueryException \$e) pattern already established in WishlistController and ReviewController.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => 'Self-service account deletion anonymizes the User row but leaves identical name, phone, and full home address intact on any Address still tied to a past order',
                'description' => "AuthController::deleteAccount() (app/Http/Controllers/Api/AuthController.php, lines 155-212) scrubs the user's name, email, and phone on the User row itself, and deletes any Address not referenced by an order -- but for the ones that are referenced (kept, per the same restrictOnDelete constraint AddressController::destroy() enforces), it does nothing at all: full_name, line1/line2, city, and phone are left exactly as they were, forever, on the addresses table. The feature is internally inconsistent: it anonymizes the customer's name and phone on users, then leaves a byte-for-byte copy of that same name and phone -- plus the full physical address, which users never even stored -- sitting on addresses, fully joinable from any order an admin opens via OrderResource's shipping_address/billing_address. A customer who deletes their account under the UI's stated expectation that their data is gone still has their real name, phone number, and home address readable from the admin order view indefinitely. Fix: anonymize the full_name/phone/address-line fields on any Address rows still referenced by an order too (leaving only what's structurally required to keep the order and invoice numerically consistent), rather than leaving them untouched.",
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
        DB::table('project_tasks')->where('title', 'A declined or failed PayPal capture never releases the stock or coupon redemption it reserved')->delete();
        DB::table('project_tasks')->where('title', 'Admin variant edit is an unlocked lost-update race that can silently un-sell stock a customer already paid for')->delete();
        DB::table('project_tasks')->where('title', "Editing a saved address after it's been used on an order retroactively rewrites that order's shipping and billing address everywhere")->delete();
        DB::table('project_tasks')->where('title', 'Admin variant create/update has a duplicate-combo TOCTOU race that surfaces a raw 500 instead of the clean 422 it was designed to produce')->delete();
        DB::table('project_tasks')->where('title', 'Self-service account deletion anonymizes the User row but leaves identical name, phone, and full home address intact on any Address still tied to a past order')->delete();
    }
};
