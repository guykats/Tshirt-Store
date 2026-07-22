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
                'title' => "advanceStatus()/refund() let a mail failure surface as a false 500 despite the order already changing state",
                'description' => "OrderController::advanceStatus() (app/Http/Controllers/Api/OrderController.php:26-28, after the \$order->update(\$attributes) commit at line 13) and refund() (line 134, after the DB::transaction commit at lines 103-121) call Mail::to(\$order->user)->locale(...)->send(new OrderShippedMail/OrderDeliveredMail/OrderRefundedMail(...)) with no surrounding try/catch. This is inconsistent with CheckoutController::store()'s OrderConfirmationMail send (app/Http/Controllers/Api/CheckoutController.php:287-289) and PayPalWebhookController::markPaid() (app/Http/Controllers/Api/PayPalWebhookController.php:75-77), both of which wrap the equivalent send in try/catch { report(\$e); } specifically so a mail outage can't 500 an already-successful action. Failure scenario: SMTP is still using deferred/placeholder credentials per project notes, or simply has an outage — an admin clicks 'Mark as Shipped' or 'Refund' in the dashboard, the order's status/payment_status is genuinely updated in the database, but the request throws a 500 from the unguarded Mail::send() call, so the admin sees a failure and may retry an action that already succeeded. Fix: wrap these three Mail::send() calls the same way the checkout-confirmation and PayPal-webhook paths already do (try/catch + report()), so notification failures never mask a successful state change.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => 'Guest checkout permanently blocks a returning guest whose earlier session already lapsed',
                'description' => "CheckoutController::store()'s guest-email collision check (app/Http/Controllers/Api/CheckoutController.php:112) is User::where('email', \$data['email'])->exists(), which matches any existing user row — including a guest row (is_guest => true) the same code path created for that shopper's own earlier order (lines 118-123). Unlike AuthController::register(), which explicitly excludes is_guest => true rows from its uniqueness check via Rule::unique('users','email')->where(fn (\$q) => \$q->where('is_guest', false)) (line 39) precisely so a guest can be claimed later, CheckoutController::store has no such exclusion. Failure scenario: a guest's session/cookie is gone (closed browser, different device, or came back weeks later — the exact case OrderController::lookup / TrackOrder.jsx exist to support) and they try to check out again with the same email; they hit the 409 'An account already exists for this email. Please log in to continue checkout.' but their account has an unusable Str::random(40) password they were never given, so they can't log in either — checkout is a dead end for any repeat guest customer. Fix: scope the exists()/lookup in CheckoutController::store to exclude is_guest => true rows (mirroring AuthController::register's pattern) and reuse the existing guest row for the new order instead of rejecting it.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => 'Admin dashboard fulfillment/refund queues re-fetch every order in the store on every load',
                'description' => "Dashboard.jsx's fetchAllPages() helper (resources/js/pages/Dashboard.jsx:28-40) sequentially awaits one /api/orders request per page (20 orders/page, looping until meta.last_page) to build a full in-memory array. loadFulfillmentOrders() (lines 70-75) calls fetchAllPages('/api/orders') with no status or payment_status filter at all — it pulls the store's entire order history on every dashboard mount just to locally .filter() for orders in NEXT_FULFILLMENT_STATUS and orders where payment_status === 'paid'. This re-runs on nearly every admin mutation too (approveOrder, advanceStatus, and refund success handlers all re-trigger loadFulfillmentOrders/loadOrders). OrderController::index() (app/Http/Controllers/Api/OrderController.php:64-90) currently only supports one exact-match status param and no payment_status param, so there is no server-side way to request just the relevant slice — every fulfillment/refund queue load necessarily fetches and discards most of the table. As this live payment site accumulates real order volume, every dashboard open becomes a growing number of sequential round-trips and will eventually be visibly slow. Fix: extend OrderController::index() to accept multi-status and/or payment_status filters, and have loadFulfillmentOrders() request only the relevant slice instead of paginating the full orders table client-side.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "Resetting a guest account's password via 'Forgot password' never clears is_guest, leaving it permanently claimable",
                'description' => "PasswordResetController::reset() (app/Http/Controllers/Api/PasswordResetController.php:34-52) sets the new password via \$user->forceFill(['password' => \$password])->save() at line 44 but never sets is_guest => false. A guest who checked out once (creating an is_guest => true row via CheckoutController::store) can use 'Forgot password?' — sendResetLink()/reset() process this for any email, including guest rows — to set a real password and get a working login. But because is_guest stays true, the row remains indistinguishable from a never-claimed guest account to every is_guest-gated check elsewhere, in particular AuthController::register()'s existingGuest claim branch (app/Http/Controllers/Api/AuthController.php:44-46), which treats any is_guest => true row as fair game to claim regardless of whether it already has a legitimately-set password. Failure scenario: the real account owner resets their password via the emailed link, then anyone who later POSTs /api/register with that same email is still routed into the guest-claim path, force-overwriting the password the owner just legitimately set, and gets logged in as that account. Fix: have PasswordResetController::reset()'s callback also set is_guest => false when resetting a guest row's password, so proving ownership via the reset-link email permanently promotes the account out of the claimable state.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'security',
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
            "advanceStatus()/refund() let a mail failure surface as a false 500 despite the order already changing state",
            'Guest checkout permanently blocks a returning guest whose earlier session already lapsed',
            'Admin dashboard fulfillment/refund queues re-fetch every order in the store on every load',
            "Resetting a guest account's password via 'Forgot password' never clears is_guest, leaving it permanently claimable",
        ])->delete();
    }
};
