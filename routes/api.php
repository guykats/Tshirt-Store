<?php

use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\Admin\ProductImageController as AdminProductImageController;
use App\Http\Controllers\Api\Admin\ProductVariantController as AdminProductVariantController;
use App\Http\Controllers\Api\AgentStatusController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\DesignController;
use App\Http\Controllers\Api\EpicController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\HomeStatsController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\PayPalWebhookController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProjectTaskController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\SiteSettingController;
use App\Http\Controllers\Api\SystemEventController;
use App\Http\Controllers\Api\TestimonialController;
use App\Http\Controllers\Api\VisionerChatController;
use App\Http\Controllers\Api\WishlistController;
use Illuminate\Support\Facades\Route;

// Unauthenticated health check for external uptime monitoring (UptimeRobot,
// Pingdom, a load balancer) and the deploy pipeline — proves the DB is
// actually reachable, not just that "/" returns a raw 200. See also the
// framework's own GET /up (bootstrap/app.php) for a simpler liveness probe.
Route::get('/health', [HealthController::class, 'show'])->middleware('throttle:health-check');

Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:register');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->middleware('throttle:forgot-password');
Route::post('/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:reset-password');

// Public catalog read surface: listing/search, product detail, and reviews.
// Per-IP throttled (see 'catalog-read' in AppServiceProvider) since none of
// these require auth and they'd otherwise be trivially scrapeable.
Route::middleware('throttle:catalog-read')->group(function () {
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{product}', [ProductController::class, 'show']);
    Route::get('/products/{product}/reviews', [ReviewController::class, 'index']);
});

// Public bootstrap read — the homepage renders logo/hero/stats for anonymous
// visitors, so this has to be reachable without a Sanctum session.
Route::get('/site-settings', [SiteSettingController::class, 'show']);

// Public homepage social-proof surface: real stats (no auth needed to read them) and
// the active testimonial quotes. Throttled with the rest of the public catalog-read
// surface since these are anonymous-facing and otherwise unguarded.
Route::middleware('throttle:catalog-read')->group(function () {
    Route::get('/home-stats', [HomeStatsController::class, 'show']);
    Route::get('/testimonials', [TestimonialController::class, 'index']);
});

Route::post('/webhooks/paypal', [PayPalWebhookController::class, 'handle']);

// Guest checkout: intentionally reachable without an authenticated session so
// a shopper isn't forced to register before buying. CheckoutController::store
// silently creates and logs in an unusable-password "guest" User behind the
// scenes when the request has no session, so the rest of this middleware
// group's user_id-based routes (capture included) work for that guest
// exactly as they would for a real registered user for the remainder of the
// browser session.
Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:checkout');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);

    Route::post('/checkout/{order}/capture', [CheckoutController::class, 'capture']);

    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::post('/orders/{order}/approve', [OrderController::class, 'approve']);
    Route::post('/orders/{order}/advance-status', [OrderController::class, 'advanceStatus']);
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel']);
    Route::post('/orders/{order}/refund', [OrderController::class, 'refund']);
    Route::get('/orders/{order}/invoice', [OrderController::class, 'invoice']);

    Route::get('/inventory/low-stock', [InventoryController::class, 'lowStock']);

    Route::get('/products/{product}/reviews/eligibility', [ReviewController::class, 'eligibility']);
    Route::post('/products/{product}/reviews', [ReviewController::class, 'store']);
    Route::delete('/products/{product}/reviews/{review}', [ReviewController::class, 'destroy']);

    Route::get('/wishlist', [WishlistController::class, 'index']);
    Route::post('/products/{product}/wishlist', [WishlistController::class, 'store']);
    Route::delete('/products/{product}/wishlist', [WishlistController::class, 'destroy']);

    Route::get('/designs', [DesignController::class, 'index']);
    Route::post('/designs/{design}/approve', [DesignController::class, 'approve']);
    Route::post('/designs/{design}/reject', [DesignController::class, 'reject']);

    Route::get('/system-events', [SystemEventController::class, 'index']);
    Route::get('/agent-statuses', [AgentStatusController::class, 'index']);
    Route::patch('/agent-statuses/{agentStatus}', [AgentStatusController::class, 'update']);

    Route::get('/activity', [ActivityController::class, 'index']);
    Route::get('/project-tasks', [ProjectTaskController::class, 'index']);

    Route::patch('/site-settings', [SiteSettingController::class, 'update']);

    // Static path before {testimonial} so "manage" is never matched as a route
    // model binding id.
    Route::get('/testimonials/manage', [TestimonialController::class, 'manage']);
    Route::post('/testimonials', [TestimonialController::class, 'store']);
    Route::patch('/testimonials/{testimonial}', [TestimonialController::class, 'update']);
    Route::delete('/testimonials/{testimonial}', [TestimonialController::class, 'destroy']);

    // Admin product & variant CRUD — separate from the public /products read
    // surface above (which route-model-binds {product} by slug and only ever
    // exposes status=active rows) so an admin can list/create/edit/delete
    // products of any status and their variants. See
    // App\Http\Controllers\Api\Admin\ProductController for the order-integrity
    // rules around deletion.
    Route::prefix('admin')->group(function () {
        Route::get('/products', [AdminProductController::class, 'index']);
        Route::post('/products', [AdminProductController::class, 'store']);
        Route::put('/products/{product}', [AdminProductController::class, 'update']);
        Route::delete('/products/{product}', [AdminProductController::class, 'destroy']);

        Route::post('/products/{product}/variants', [AdminProductVariantController::class, 'store']);
        Route::put('/products/{product}/variants/{variant}', [AdminProductVariantController::class, 'update']);
        Route::delete('/products/{product}/variants/{variant}', [AdminProductVariantController::class, 'destroy']);

        // Static "reorder" path before {image} so it's never matched as a route model
        // binding id, same pattern as GET /testimonials/manage above.
        Route::patch('/products/{product}/images/reorder', [AdminProductImageController::class, 'reorder']);
        Route::post('/products/{product}/images', [AdminProductImageController::class, 'store']);
        Route::put('/products/{product}/images/{image}', [AdminProductImageController::class, 'update']);
        Route::delete('/products/{product}/images/{image}', [AdminProductImageController::class, 'destroy']);

        // Cross-product review moderation listing — see App\Http\Controllers\Api\ReviewController::manage.
        // The actual DELETE lives at /api/products/{product}/reviews/{review} above (outside this
        // admin/ prefix) since it's scoped under a specific product, matching how reviews are
        // addressed everywhere else in the app.
        Route::get('/reviews', [ReviewController::class, 'manage']);
    });

    Route::get('/epics', [EpicController::class, 'index']);
    Route::post('/epics/{epic}/approve', [EpicController::class, 'approve']);
    Route::post('/epics/{epic}/reject', [EpicController::class, 'reject']);
    Route::post('/epics/{epic}/delay', [EpicController::class, 'delay']);

    Route::get('/visioner-chat', [VisionerChatController::class, 'index']);
    Route::post('/visioner-chat', [VisionerChatController::class, 'store'])->middleware('throttle:visioner-chat');
});
