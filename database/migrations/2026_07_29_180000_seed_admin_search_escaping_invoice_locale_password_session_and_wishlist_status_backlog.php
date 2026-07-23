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
                'title' => "Admin coupon and product search endpoints don't escape SQL LIKE wildcards, unlike the public catalog search",
                'description' => "Admin\\CouponController::index (app/Http/Controllers/Api/Admin/CouponController.php:27) builds '%'.strtoupper(\$search).'%' directly, and Admin\\ProductController::index (app/Http/Controllers/Api/Admin/ProductController.php:36-37) builds '%'.\$search.'%' directly for both name and sku - neither escapes literal % or _ characters typed by the admin. The public ProductController::index (app/Http/Controllers/Api/ProductController.php:29) already does str_replace(['%', '_'], ['\\\\%', '\\\\_'], \$search) before building its LIKE pattern, showing this escaping is a known-necessary pattern simply never applied to the two admin search endpoints. An admin searching coupons or products for a code/name containing a literal underscore (e.g. SAVE_10) gets '_' treated as a SQL single-character wildcard, silently matching unrelated rows instead of the literal string typed. Fix: reuse the same escape-before-LIKE helper in both admin search methods that the public ProductController::index already uses.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "The PDF invoice's Payment Status value is never translated for Hebrew-locale invoices",
                'description' => "resources/views/invoices/order.blade.php:136 renders {{ ucfirst(\$order->payment_status) }} - the raw enum value (paid, unpaid, failed, refunded) with only its first letter capitalized, never passed through __(). lang/en/invoice.php and lang/he/invoice.php only define a translation for the field label ('payment_status' => 'Payment Status'/'סטטוס תשלום'), with no keys at all for the four possible values. InvoiceService::generate() (app/Services/InvoiceService.php:18-21) explicitly sets the app locale to the order owner's preferred_locale before rendering, so a Hebrew-locale customer's invoice ends up with the English word 'Paid' or 'Refunded' sitting inside an otherwise fully-Hebrew, RTL document. Fix: add paid/unpaid/failed/refunded keys to both invoice.php lang files and map through them instead of ucfirst().",
                'agent_name' => 'Creative Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "Changing your password doesn't invalidate any other active session for the same account",
                'description' => "AuthController::changePassword (app/Http/Controllers/Api/AuthController.php:114-132) re-verifies the current password and saves the new hash, but - unlike logout() (lines 92-100) and deleteAccount(), which both call \$request->session()->invalidate()/regenerateToken() - it never touches the session store, and never calls Auth::logoutOtherDevices(). Since auth here is Sanctum's stateful, cookie-based session (not per-device tokens), any other already-authenticated session for that user (a stolen cookie, a forgotten logged-in browser on a shared computer) keeps working with full access after the password is changed, defeating the standard 'change password to lock out an intruder' expectation. Fix: call Auth::logoutOtherDevices(\$data['password']) inside changePassword, mirroring the session-invalidation pattern already used by logout()/deleteAccount().",
                'agent_name' => 'Ops Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "Wishlisting a product bypasses the 'must be active' gate that the public catalog and product page enforce everywhere else",
                'description' => "WishlistController::store (app/Http/Controllers/Api/WishlistController.php:35) binds Product \$product straight off the /products/{product}/wishlist route (routes/api.php:116) with no status check at all, while the public ProductController::show does abort_unless(\$product->status === 'active', 404) and index() filters to status = 'active' only. Any authenticated customer who guesses or is sent a draft/archived product's slug can POST it to their wishlist, and WishlistController::index/WishlistItemResource then return that product's full ProductResource (name, price, description, images) to them - an unreleased product leaking outside the intended draft-review flow. Fix: add abort_unless(\$product->status === 'active', 404) in WishlistController::store, mirroring ProductController::show.",
                'agent_name' => 'Ops Agent',
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
        DB::table('project_tasks')->where('title', "Admin coupon and product search endpoints don't escape SQL LIKE wildcards, unlike the public catalog search")->delete();
        DB::table('project_tasks')->where('title', "The PDF invoice's Payment Status value is never translated for Hebrew-locale invoices")->delete();
        DB::table('project_tasks')->where('title', "Changing your password doesn't invalidate any other active session for the same account")->delete();
        DB::table('project_tasks')->where('title', "Wishlisting a product bypasses the 'must be active' gate that the public catalog and product page enforce everywhere else")->delete();
    }
};
