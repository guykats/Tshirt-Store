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
                'title' => 'CI never runs the frontend test suite (vitest) or lint, so ~20+ React tests execute nowhere',
                'description' => ".github/workflows/tests.yml runs `npm ci` and `npm run build` (to produce the Vite manifest the root route needs) but never `npm test` or `npm run lint`, even though package.json defines both (\"test\": \"vitest run\", \"lint\": \"eslint .\") and the repo has ~20+ files under resources/js/**/__tests__/*.test.jsx (Checkout.test.jsx, CouponManagement.test.jsx, ProductDetail.test.jsx, Layout.test.jsx, etc.). Only php artisan test gates CI pass/fail today, so a broken React component or a failing frontend test can merge to main and deploy without CI ever noticing. Fix by adding an `npm test` (and ideally `npm run lint`) step to tests.yml alongside the existing build step.",
                'agent_name' => 'Ops Agent',
                'task_type' => 'quality',
            ],
            [
                'title' => 'Checkout capture and order-lifecycle admin mutation routes (capture/approve/advance-status/cancel/refund) carry no rate limiting',
                'description' => "routes/api.php:110 (POST /api/checkout/{order}/capture) and routes/api.php:114-117 (POST /api/orders/{order}/approve, advance-status, cancel, refund) have no throttle: middleware. Laravel 11+'s default api middleware group applies no implicit throttle unless Middleware::throttleApi() is called in bootstrap/app.php, and this app only calls statefulApi() there, not throttleApi() — so these routes really have zero rate ceiling, not an inherited baseline. An authenticated attacker or a compromised/leaked session can hammer PayPal capture attempts or repeatedly call cancel/refund/advance-status with no throttling, risking PayPal API rate-limit bans on the merchant account and amplifying refund race conditions. Fix by adding a throttle: limiter to this route group, mirroring the pattern already used for checkout, account-security, and reviews.",
                'agent_name' => 'Ops Agent',
                'task_type' => 'security',
            ],
            [
                'title' => "OrderController::refund calls PayPal's refund API before the row-locked payment_status recheck, risking a duplicate real-money refund",
                'description' => "app/Http/Controllers/Api/OrderController.php:239-281: the only guard before calling \$payPal->refundCapture(\$order->paypal_transaction_id) (line 252) is an unlocked payment_status !== 'paid' check (line 243). The actual lockForUpdate() + recheck of payment_status happens only after the PayPal call, inside the DB::transaction at lines 262-277. Two near-simultaneous refund requests for the same order (double-click, or two admin tabs) can both pass the initial check and both invoke PayPal's refund endpoint for the same paypal_transaction_id before either has updated local state — PayPalClient::refundCapture (app/Services/PayPalClient.php:95) sends no idempotency key, so this isn't self-protecting. Real money can be refunded twice via PayPal, or the second call's RuntimeException surfaces as a generic 'refund failed, try again' 502 while PayPal already processed something. Fix by acquiring the row lock and rechecking payment_status before calling PayPal, not after.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => "Admin product-image url field is validated as a plain string, not a URL, so a non-URL value saves with no validation error",
                'description' => "app/Http/Controllers/Api/Admin/ProductImageController.php:113-120's validated() validates 'url' as ['required', 'string', 'max:500'] rather than including Laravel's url validation rule, unlike other admin forms in this codebase that validate URL-shaped fields properly. An admin can save an arbitrary non-URL string (a typo, stray whitespace, or plain text) into a product image row with no validation error, and it will silently fail to render as an image on the storefront. Fix by adding the url rule alongside string/max:500.",
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
            'CI never runs the frontend test suite (vitest) or lint, so ~20+ React tests execute nowhere',
            'Checkout capture and order-lifecycle admin mutation routes (capture/approve/advance-status/cancel/refund) carry no rate limiting',
            "OrderController::refund calls PayPal's refund API before the row-locked payment_status recheck, risking a duplicate real-money refund",
            'Admin product-image url field is validated as a plain string, not a URL, so a non-URL value saves with no validation error',
        ])->delete();
    }
};
