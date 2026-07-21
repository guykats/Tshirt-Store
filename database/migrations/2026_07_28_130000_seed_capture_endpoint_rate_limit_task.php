<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')->insert([
            'epic_id' => null,
            'title' => 'POST /api/checkout/{order}/capture has no rate limiting, unlike every sibling payment/account route',
            'description' => 'routes/api.php line 98 - Route::post(\'/checkout/{order}/capture\', [CheckoutController::class, \'capture\']) - has no throttle middleware, and app/Providers/AppServiceProvider.php defines no RateLimiter::for(\'capture\', ..) to attach. Every comparable sensitive/external-API route already has one: /checkout has throttle:checkout (routes/api.php:72), /orders/lookup has throttle:order-lookup (:80), /change-password and DELETE /account have throttle:account-security (:85-86), the review endpoints have throttle:reviews (:111-113), /visioner-chat has throttle:visioner-chat (:191) - all backed by matching RateLimiter::for(..) definitions in AppServiceProvider (lines 26-103). CheckoutController::capture() (lines 243-295) calls out to $this->payPal->captureOrder($order->paypal_order_id) - a live external PayPal API call - on every hit where payment_status !== \'paid\'. A failed capture sets payment_status to \'failed\' (line 267), not a terminal state, so a buyer (including an auto-created guest per CheckoutController::store\'s comment) can hit this endpoint against their own order unboundedly, generating unlimited PayPal API traffic and log/report() churn per request at zero cost to them - the same class of abuse the checkout/reviews/account-security limiters were added to close, just missed for this one route. Add a RateLimiter::for(\'capture\', ..) definition (mirroring the per-user throttle shape already used for account-security/checkout - not a bare per-IP limit, since this route is behind auth) and attach throttle:capture to the route. Feature test: assert repeated capture attempts past the limit return 429.',
            'agent_name' => 'Ops Agent',
            'task_type' => 'security',
            'status' => 'todo',
            'approved_for_dev' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'POST /api/checkout/{order}/capture has no rate limiting, unlike every sibling payment/account route')
            ->delete();
    }
};
