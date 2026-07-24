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
                'title' => "Checkout never calculates tax_amount or shipping_amount, so every order, invoice, and email shows \$0.00 for both regardless of destination or order size",
                'description' => "The orders table has real tax_amount and shipping_amount columns, both exposed on OrderResource and rendered as explicit line items on the PDF invoice (resources/views/invoices/order.blade.php). But CheckoutController::store() (app/Http/Controllers/Api/CheckoutController.php) only ever sets total_amount = subtotal - discount_amount — it never assigns tax_amount or shipping_amount, so both columns sit at their DB default of 0.00 on every order ever placed, regardless of destination or order value. The invoice, order-confirmation email, and account order history all faithfully render 'Tax: \$0.00' and 'Shipping: \$0.00' as if that were a real quote rather than an unimplemented feature. Fix: either wire an actual tax/shipping calculation into checkout (even a flat-rate shipping fee and a simple tax-percentage config would be more honest than always-zero) or remove the tax/shipping line items from the invoice and emails until the feature exists, so customers aren't shown fabricated \$0.00 charges that look like a broken discount rather than 'not implemented'.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "Customers have no way to change their name or phone number after registering — no profile-edit endpoint exists anywhere in the app",
                'description' => "UserResource exposes name and phone, and both are collected once at registration (AuthController::register). But AccountSettings.jsx only ever renders a change-password form, the saved-address book, and account deletion — there is no name/phone input anywhere in the file — and routes/api.php has no PATCH /me or /account/profile route at all; the only authenticated AuthController mutations are changePassword and deleteAccount. A customer who registers with a typo in their name, or whose phone number changes, has no in-app path to correct it short of deleting and recreating their account, which also destroys their saved addresses and order-review linkage. Fix: add a PATCH /api/me endpoint validating and updating name/phone (mirroring changePassword's pattern since these aren't security-sensitive fields), and a corresponding form section in AccountSettings.jsx.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "The privacy policy promises customers can request a data export, but no code path anywhere can produce one",
                'description' => "Privacy.jsx's 'Your Rights and Choices' section states, in both locales (resources/js/i18n/index.js, privacy_s6_p1 key): 'You may also ask us to export or delete your personal data, and we will honor that request.' Account deletion is genuinely implemented (AuthController::deleteAccount), but export is not: there is no export endpoint in routes/api.php, no admin-facing customer lookup/detail page anywhere under resources/js/pages, and no service or command that assembles a user's orders, addresses, reviews, and wishlist into a downloadable payload. A support request to honor this stated right currently has no tooling to fulfill it other than an engineer manually querying the database. Fix: add a self-service POST /api/account/export (current-password-gated like deleteAccount) that compiles the user's own orders/addresses/reviews/wishlist into a JSON or PDF download, or at minimum an admin-only lookup+export tool, so the promise in the privacy policy is actually backed by functionality.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "Fixed-value coupons have no currency of their own, so the same raw number is deducted regardless of what currency the order is actually priced in",
                'description' => "Product.currency is a free-typed 3-letter code an admin sets per product (validated only as size:3 in Admin/ProductController, with no enum restriction), so two products in the catalog can legitimately be priced in different currencies (e.g. USD and ILS). But Coupon has no currency column at all, and Coupon::discountFor() does pure arithmetic — subtotal * (value/100) for percent, or the raw value for fixed — with zero awareness of the subtotal's currency. A '\$10 off' fixed coupon minted with USD prices in mind will silently deduct 10 raw units from an ILS-denominated order too (worth roughly 3x less in real terms), and nothing blocks a fixed coupon from being redeemed against an order in a currency it was never designed for. Fix: add a nullable currency column to coupons (null = any currency, recommended for percent-only), validate that a fixed-value coupon's currency matches the order's before allowing redemption, and reject mismatches with a clear error.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "PayPalWebhookController never handles a PAYMENT.CAPTURE.REFUNDED event, so a refund issued directly in PayPal never updates the local order",
                'description' => "PayPalWebhookController::handle()'s event dispatch only matches PAYMENT.CAPTURE.COMPLETED (-> markPaid) and PAYMENT.CAPTURE.DENIED (-> markFailed); every other event type, including PAYMENT.CAPTURE.REFUNDED, falls through to the default => Log::info(...) arm and is otherwise ignored. The app's own admin-initiated refund flow (OrderController::refund()) updates payment_status/status to refunded directly, but that's the only way a refund is ever reflected locally — if a refund happens outside that button (PayPal support processes a buyer dispute, a chargeback is upheld, or staff issue the refund from PayPal's own dashboard instead of this app), PayPal sends a PAYMENT.CAPTURE.REFUNDED webhook that this handler silently drops. The order is left showing payment_status: paid and fulfillable/shippable in the admin dashboard indefinitely, even though the money has already been returned to the buyer on PayPal's side. Fix: add a markRefunded() handler for PAYMENT.CAPTURE.REFUNDED that mirrors markFailed's idempotent status-guarded update, restores stock via OrderStockService, and logs a SystemEvent, matching how markPaid already handles the completed-capture case.",
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
        DB::table('project_tasks')->where('title', "Checkout never calculates tax_amount or shipping_amount, so every order, invoice, and email shows \$0.00 for both regardless of destination or order size")->delete();
        DB::table('project_tasks')->where('title', "Customers have no way to change their name or phone number after registering — no profile-edit endpoint exists anywhere in the app")->delete();
        DB::table('project_tasks')->where('title', "The privacy policy promises customers can request a data export, but no code path anywhere can produce one")->delete();
        DB::table('project_tasks')->where('title', "Fixed-value coupons have no currency of their own, so the same raw number is deducted regardless of what currency the order is actually priced in")->delete();
        DB::table('project_tasks')->where('title', "PayPalWebhookController never handles a PAYMENT.CAPTURE.REFUNDED event, so a refund issued directly in PayPal never updates the local order")->delete();
    }
};
