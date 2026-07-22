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
                'title' => 'Admin refund action in the Dashboard has zero error feedback on failure',
                'description' => "Dashboard.jsx's refundOrder() (resources/js/pages/Dashboard.jsx:156-166) awaits api.post(`/api/orders/\${id}/refund`) with only a try/finally — no catch. OrderController::refund() (app/Http/Controllers/Api/OrderController.php:251-257) has a real, documented failure path: it returns HTTP 502 when the PayPal refund call throws. When that happens, the promise rejection here is never caught — the button's spinner just stops via finally, with no message and no role=\"alert\". This directly contradicts the sibling handler three functions above it, advanceOrderStatus() (Dashboard.jsx:134-154), which does catch and surface shippingErrors via setShippingErrors. Failure scenario: PayPal is briefly unreachable when an admin clicks 'Refund' on a paid order — the button stops spinning, the order still shows as paid/unrefunded, and the admin has no indication anything went wrong or that they should retry, and may assume the refund succeeded. Fix: wrap refundOrder()'s api.post call in try/catch, mirroring advanceOrderStatus()'s pattern, and surface a translated error near the Refund button.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "Guest checkout's duplicate-email check has a TOCTOU race that crashes with a raw 500",
                'description' => "CheckoutController::store()'s guest-email branch (app/Http/Controllers/Api/CheckoutController.php:111-123) does User::where('email', \$data['email'])->exists() and, if false, immediately calls User::create([...'email' => \$data['email']...]) — with no guard against another request creating the same row in between. users.email is unique-constrained (database/migrations/0001_01_01_000000_create_users_table.php:17). This exact exists()-then-create() race is precisely what WishlistController::store (app/Http/Controllers/Api/WishlistController.php:49-61) and ReviewController::store (app/Http/Controllers/Api/ReviewController.php:79-92) explicitly guard against with a try { create() } catch (QueryException) fallback — CheckoutController::store has no equivalent. Failure scenario: a shopper double-clicks 'Place order' or has two tabs open, both guest-checkout requests race with the same brand-new email; the second User::create() throws an uncaught QueryException on the unique constraint, surfacing to the buyer as a bare 500 Internal Server Error instead of the clean 409 'An account already exists' message the code was clearly designed to return. Fix: wrap the User::create() call in the same try/catch(QueryException)-then-refetch pattern already used by WishlistController and ReviewController.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "OrderCard's order date ignores the active site language while the price three lines below it doesn't",
                'description' => "OrderCard.jsx (resources/js/components/OrderCard.jsx) destructures i18n from useTranslation() and correctly passes i18n.language into formatPrice(order.total_amount, order.currency, i18n.language) at line 81, but the order date at line 38 calls new Date(order.created_at).toLocaleDateString() with no locale argument at all, so it renders in the browser/OS default locale instead of the shopper's chosen site language. OrderCard is shared by both the authenticated Orders page and the public Track-Order page. Failure scenario: a Hebrew-UI shopper whose browser locale is en-US views their order history — every price is correctly Hebrew-formatted via i18n.language, but every order date renders in plain MM/DD/YYYY English formatting, visibly inconsistent within the same card. The same unguarded toLocaleDateString() pattern also appears in resources/js/pages/CouponManagement.jsx:242 and resources/js/pages/Dashboard.jsx:409, so the fix should cover all three call sites, not just OrderCard. Fix: pass i18n.language (mapped to a BCP-47 locale tag, e.g. 'he' -> 'he-IL') into every toLocaleDateString() call so dates follow the active site language like prices already do.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => 'Register.jsx shows Laravel\'s raw, untranslated "invalid data" message instead of the actual validation reason',
                'description' => "Register.jsx's handleSubmit() catch block (resources/js/pages/Register.jsx:30) only reads err.response?.data?.message, falling back to t('register_error'). AuthController::register() (app/Http/Controllers/Api/AuthController.php:31-43) throws Laravel's ValidationException via \$request->validate(...), whose top-level JSON message is always the generic, English-only 'The given data was invalid.' — the real per-field reason (email already taken, password too short, confirmation mismatch) only lives inside the response's errors object. AccountSettings.jsx already establishes the correct pattern for this exact API shape: handleSubmit/handleDeleteAccount (resources/js/pages/AccountSettings.jsx:136-145, 154-163) inspect err.response.data.errors.<field> and map each to a specific, translated string. Register.jsx does not follow that pattern. Failure scenario: a Hebrew-UI visitor registers with an email that already belongs to a real account, or mistypes their password confirmation — instead of an actionable, translated message, they see the literal English string 'The given data was invalid.' with no clue what to fix, breaking the bilingual UI on the account-creation form specifically. Fix: inspect err.response.data.errors in Register.jsx's catch block and map known field errors (email, password) to translated strings, the same way AccountSettings.jsx already does.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "ProductDetail's Buy Now link stays fully clickable when the selected variant is out of stock",
                'description' => "ProductDetail.jsx's Buy Now Link (resources/js/pages/ProductDetail.jsx:204-211) is only styled/disabled via pointer-events-none when selected is falsy (no variant at all matches the chosen size+color). When a matching variant row exists but has stock_quantity === 0, selected is still truthy, so the link keeps its fully-clickable styling and live to= target — only the label text switches to t('checkout_out_of_stock') at line 210. The size-swatch buttons a few lines above correctly gate on stock via disabled={!inStock} (line 191, checking v.stock_quantity > 0), but the CTA link below them does not apply the same check. Failure scenario: a shopper picks a color where their preferred size is sold out (the variant row exists with stock_quantity: 0); the button visibly reads 'Out of Stock' yet is still a live link — clicking it navigates straight into /checkout/:slug?variant=..., and only fails once they've filled in a full shipping address and hit submit, where CheckoutController::store (line 135-136) finally rejects it with 'Not enough stock for the requested quantity.' Fix: also gate the Link's to= target and pointer-events-none styling on selected.stock_quantity > 0, matching the size-swatch buttons' existing inStock check.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "Every Dashboard admin widget fetch has no .catch() — a failed request looks like an empty widget",
                'description' => "Dashboard.jsx's five widget loaders — loadAgents(), loadEvents(), loadActivity(), loadTaskCounts(), and loadLowStock() (resources/js/pages/Dashboard.jsx:77-95), all fired on mount via the useEffect starting at line 97 — call api.get(...).then(res => setX(res.data...)) with no .catch() at all. On failure, state (agents, events, activity, taskCounts, lowStock) simply never updates and stays at its initial empty value, rendering identically to 'nothing to show' for that widget. This is the same failure class already flagged for Wishlist/account-addresses fetches on the customer side, but that fix doesn't touch the admin Dashboard's widgets. Failure scenario: an admin whose session has just expired, or who hits a transient 500/network blip on page load, sees an apparently-healthy dashboard showing 'no agents,' 'no recent activity,' 'no low-stock items,' etc. — with no indication any of the five requests actually failed, potentially masking a real low-stock or system-event alert. Fix: add .catch() to each of the five loaders, surfacing a visible (even if lightweight, e.g. a small inline notice per widget) failure state instead of silently rendering empty.",
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
            'Admin refund action in the Dashboard has zero error feedback on failure',
            "Guest checkout's duplicate-email check has a TOCTOU race that crashes with a raw 500",
            "OrderCard's order date ignores the active site language while the price three lines below it doesn't",
            'Register.jsx shows Laravel\'s raw, untranslated "invalid data" message instead of the actual validation reason',
            "ProductDetail's Buy Now link stays fully clickable when the selected variant is out of stock",
            "Every Dashboard admin widget fetch has no .catch() — a failed request looks like an empty widget",
        ])->delete();
    }
};
