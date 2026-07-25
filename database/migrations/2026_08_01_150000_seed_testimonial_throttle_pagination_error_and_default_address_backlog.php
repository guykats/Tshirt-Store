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
                'title' => 'Testimonial admin create/update/delete routes carry no rate limiting, unlike every other authenticated write surface in the app',
                'description' => "routes/api.php:142-144 registers POST /testimonials, PATCH /testimonials/{testimonial}, and DELETE /testimonials/{testimonial} inside the auth:sanctum group with no throttle: middleware, and AppServiceProvider::boot() defines no RateLimiter::for('testimonials', ...) limiter at all -- its only limiters are login, register, forgot-password, reset-password, checkout, order-lookup, visioner-chat, reviews, catalog-read, health-check, and account-security. Every comparable authenticated write route (reviews, in the same route file, uses throttle:reviews) is protected; TestimonialController::store/update/destroy is the one CRUD surface with none. Fix: add a testimonials rate limiter and apply throttle:testimonials to all three routes.",
                'agent_name' => 'Ops Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "CheckoutController never marks a newly created shipping address as the customer's default, permanently breaking the 'first address becomes default' invariant",
                'description' => "CheckoutController.php:184-187 creates a fresh Address via \$buyer->addresses()->create(['type' => 'shipping', ...\$data['shipping_address']]) with no is_default key, so it saves as false. AddressController::store() (AddressController.php:58-72) only auto-promotes an address to default when \$user->addresses()->doesntExist() is true at save time. A logged-in customer whose very first address ever comes from checking out (rather than pre-visiting the address book) ends up with zero default addresses, and the next address they add via AddressController::store() also skips the 'first address' promotion since one already exists -- the account can be left with no is_default address until they manually call setDefault. This breaks Checkout.jsx's list.find(a => a.is_default) preselect and the 'default' badge in AccountSettings.jsx. Fix: have CheckoutController set is_default to the same doesntExist() check AddressController uses before creating the address.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => 'TestimonialController::manage() has no pagination or row limit, unlike every other admin list endpoint in the app',
                'description' => "TestimonialController.php:32-39 (manage(), the admin CMS listing DesignSettings.jsx renders) runs Testimonial::query()->orderBy('sort_order')->orderBy('id')->get() with no paginate() or limit() call, returning every row in one response regardless of table size. Every comparable admin listing in the codebase paginates (ProductController::index, AdminCouponController::index, ReviewController::manage, SystemEventController::index) -- this is the same class of gap already flagged for EpicController::index but on a separate, previously-unmentioned endpoint. Fix: paginate manage() the same way the other admin listings do and update DesignSettings.jsx to consume the paginated shape.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "DesignSettings.jsx's testimonials list load has no error handling, so a failed fetch looks identical to 'no testimonials configured yet'",
                'description' => "DesignSettings.jsx:77-81 (loadTestimonials()) calls api.get('/api/testimonials/manage').then((res) => setTestimonials(res.data.data)).finally(() => setTestimonialsLoading(false)) with no .catch(). On a failed request, testimonials stays at its initial empty-array state, which the render at line 334-335 shows as design_settings_testimonials_empty -- indistinguishable from an admin who has genuinely never added any testimonials, risking an admin re-creating duplicate homepage social-proof content that was never actually lost. This is a separate fetch and failure mode from the main site-settings/hero form already flagged in task 148. Fix: add a .catch() that sets a distinct error state and renders it instead of the empty-state copy.",
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
            'Testimonial admin create/update/delete routes carry no rate limiting, unlike every other authenticated write surface in the app',
            "CheckoutController never marks a newly created shipping address as the customer's default, permanently breaking the 'first address becomes default' invariant",
            'TestimonialController::manage() has no pagination or row limit, unlike every other admin list endpoint in the app',
            "DesignSettings.jsx's testimonials list load has no error handling, so a failed fetch looks identical to 'no testimonials configured yet'",
        ])->delete();
    }
};
