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
                'title' => "AuthController::register has a TOCTOU race that crashes with a raw 500 on concurrent double-submit",
                'description' => "app/Http/Controllers/Api/AuthController.php:31-65 validates the email as unique among non-guest users (Rule::unique('users','email')->where(fn (\$q) => \$q->where('is_guest', false)), line 39) and, when no existing guest row is found, calls User::create(\$data) at line 65 with no surrounding try/catch. Two concurrent POST /api/register requests for the same brand-new email (a double-tapped submit button, or two tabs) can both pass the uniqueness check before either commits, then both fall into the else branch at line 64-65 -- the second User::create() hits the DB-level unique constraint on users.email (0001_01_01_000000_create_users_table.php:17) and throws an uncaught Illuminate\\Database\\QueryException, surfacing to the user as an unhandled 500 instead of the clean 'email taken' validation error the Rule::unique check was meant to guarantee. Fix by catching the unique-constraint violation around the create() call and returning the same validation error shape as the normal Rule::unique rejection.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => "Changing your password never signs out any other active session on the same account",
                'description' => "AuthController::changePassword (app/Http/Controllers/Api/AuthController.php:114-131) verifies the current password and force-fills a new one at line 129, but never calls Auth::logoutOtherDevices() or invalidates any other session, and there's no AuthenticateSession middleware wiring in this app to make Sanctum's session-cookie auth re-check the password hash per request. A user who suspects their account is compromised (session left open on a shared/public device, or leaked cookie) and changes their password from a trusted device gets zero security benefit -- the other session keeps working exactly as before, indefinitely. Fix by calling Auth::logoutOtherDevices(\$data['password']) (or equivalent Sanctum token revocation) after the password update succeeds.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => "Admin variant edit has no row lock, unlike every other stock-touching path in the app",
                'description' => "ProductVariantController::update (app/Http/Controllers/Api/Admin/ProductVariantController.php:40-51) reads the ProductVariant via route-model binding and calls \$variant->update(\$data) at line 51 with no lockForUpdate(), while CheckoutController::store and OrderController::cancel/refund all explicitly row-lock ProductVariant before touching stock_quantity for exactly this reason. Failure scenario: an admin opens the edit form for a variant showing stock_quantity=10; before they submit, a customer checks out and the locked decrement in CheckoutController::store brings real stock to 7; the admin's stale form (e.g. only changing price_override, but still carrying the old stock_quantity=10 in its payload) submits and overwrites stock_quantity back to 10 -- silently re-exposing 3 units that were already sold and paid for. Fix by wrapping the read+update in the same lockForUpdate() pattern used elsewhere, or by excluding stock_quantity from the admin edit payload in favor of an explicit adjustment action.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => "Self-service account deletion leaves full name, phone, and home address exposed on any address still linked to a past order",
                'description' => "AuthController::deleteAccount (app/Http/Controllers/Api/AuthController.php:155-196) only hard-deletes an Address row when it is NOT referenced by any order (the \$referencedByOrder check at lines 178-184) -- every address that WAS used on an order, the common case for anyone who ever checked out, is left completely untouched with its real full_name/phone/line1/line2/city/state/postal_code intact. Only the User row itself gets anonymized (lines 189-196). This directly contradicts the UI's own copy (resources/js/i18n/index.js:441 and :1200, the account_delete_warning string), which tells the customer 'Your order history is kept for our records but is no longer linked to your personal details.' In reality OrderResource still serves the real name/phone/address to any admin viewing that order via shippingAddress/billingAddress -- the order stays fully linked to the customer's real PII forever. Fix by scrubbing the PII fields on order-referenced addresses too (name/phone/address text) while leaving the row itself in place for order-record integrity, and update the warning copy only if some PII is intentionally kept.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
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
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    public function down(): void
    {
        DB::table('project_tasks')->whereIn('title', [
            "AuthController::register has a TOCTOU race that crashes with a raw 500 on concurrent double-submit",
            "Changing your password never signs out any other active session on the same account",
            "Admin variant edit has no row lock, unlike every other stock-touching path in the app",
            "Self-service account deletion leaves full name, phone, and home address exposed on any address still linked to a past order",
        ])->delete();
    }
};
