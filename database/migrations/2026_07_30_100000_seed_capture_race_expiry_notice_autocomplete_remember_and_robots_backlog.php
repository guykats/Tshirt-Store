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
                'title' => "A PayPal capture can still mark payment_status paid on an order that already auto-expired to cancelled",
                'description' => "CheckoutController::capture() (app/Http/Controllers/Api/CheckoutController.php:242-267) and PayPalWebhookController::markPaid() (app/Http/Controllers/Api/PayPalWebhookController.php:49-70) both only guard against \$order->payment_status === 'paid' before writing payment_status = 'paid' - neither checks \$order->status, and neither acquires a row lock. ExpireAbandonedOrders (app/Console/Commands/ExpireAbandonedOrders.php:32-58) already defends its own side of this race with lockForUpdate() + a re-checked isCancellable(), but that only protects the cancel-and-restore-stock path, not the capture path: a buyer whose PayPal approval completes in the same window the scheduled command cancels their order and restores its reserved stock can still have the capture endpoint or the webhook land afterward and set payment_status to 'paid' on a status='cancelled' order, with the variant stock already given back to other shoppers. Fix: have capture() and markPaid() re-check (ideally under a row lock, mirroring OrderController::cancel()/refund()) that the order is still in a payable status before writing payment_status = 'paid'.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "Auto-expired abandoned orders never notify the customer, unlike every other order-status change",
                'description' => "ExpireAbandonedOrders::handle() (app/Console/Commands/ExpireAbandonedOrders.php:59-73) cancels the order, restores stock, and writes a SystemEvent - but never sends mail. Every other status transition in the app (ship, deliver, refund, admin-cancel) emails the customer; a shopper whose reserved checkout silently expires after config('checkout.reservation_minutes') gets no explanation and has to notice the order missing/cancelled on their own. Fix: send a cancellation-notice mail (reusing the existing OrderConfirmationMail-style pattern with locale(\$order->user->preferred_locale ?? 'en')) from the same transaction that cancels the order, matching how OrderController's other transitions already notify.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "No form field anywhere in the app sets an autoComplete attribute, breaking password-manager autofill and failing WCAG 1.3.5",
                'description' => "A repo-wide search of resources/js turns up zero occurrences of autoComplete on any <input>, including Login.jsx, Register.jsx, and the checkout/account address forms. Without autoComplete=\"email\"/\"current-password\"/\"new-password\"/\"street-address\" etc., browsers and password managers can't reliably identify these fields, degrading autofill and failing WCAG 2.1 success criterion 1.3.5 (Identify Input Purpose), which this repo otherwise treats as a hard accessibility bar (see DesignArt.jsx's aria-label/role conventions already enforced elsewhere). Fix: add the correct autoComplete value to every credential, contact, and address input site-wide.",
                'agent_name' => 'Creative Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "Login.jsx has no 'remember me' checkbox even though AuthController already honors the remember flag",
                'description' => "AuthController::login (app/Http/Controllers/Api/AuthController.php:81) already calls Auth::attempt(\$credentials, \$request->boolean('remember')), but Login.jsx (resources/js/pages/Login.jsx) never renders a checkbox or sends a remember field, so the parameter is permanently false in practice and every session behaves as non-persistent regardless of user intent. Fix: add a labelled 'remember me' checkbox to Login.jsx (with matching i18n keys) and include its value in the login request body.",
                'agent_name' => 'Creative Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "robots.txt has an empty Disallow, letting crawlers index /dashboard, /account, and /checkout",
                'description' => "public/robots.txt is just 'User-agent: *' with a bare 'Disallow:' (which disallows nothing) plus a Sitemap line - there is no path exclusion anywhere, and no per-route <meta name=\"robots\"> override either (app.blade.php only ever sets a single site-wide description meta). Search engines are free to crawl and index admin-only paths (/dashboard/*) and personal account/checkout pages (/account, /orders, /checkout/*), none of which should appear in search results. Fix: add Disallow rules for /dashboard, /account, /orders, /checkout, /login, /register to robots.txt, and/or a noindex meta tag on those SPA routes.",
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
        DB::table('project_tasks')->where('title', "A PayPal capture can still mark payment_status paid on an order that already auto-expired to cancelled")->delete();
        DB::table('project_tasks')->where('title', "Auto-expired abandoned orders never notify the customer, unlike every other order-status change")->delete();
        DB::table('project_tasks')->where('title', "No form field anywhere in the app sets an autoComplete attribute, breaking password-manager autofill and failing WCAG 1.3.5")->delete();
        DB::table('project_tasks')->where('title', "Login.jsx has no 'remember me' checkbox even though AuthController already honors the remember flag")->delete();
        DB::table('project_tasks')->where('title', "robots.txt has an empty Disallow, letting crawlers index /dashboard, /account, and /checkout")->delete();
    }
};
