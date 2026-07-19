<?php

use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\AgentStatusController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\DesignController;
use App\Http\Controllers\Api\EpicController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\PayPalWebhookController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProjectTaskController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\SiteSettingController;
use App\Http\Controllers\Api\SystemEventController;
use App\Http\Controllers\Api\VisionerChatController;
use Illuminate\Support\Facades\Route;

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

Route::post('/webhooks/paypal', [PayPalWebhookController::class, 'handle']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::post('/checkout', [CheckoutController::class, 'store']);
    Route::post('/checkout/{order}/capture', [CheckoutController::class, 'capture']);

    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::post('/orders/{order}/approve', [OrderController::class, 'approve']);
    Route::get('/orders/{order}/invoice', [OrderController::class, 'invoice']);

    Route::get('/products/{product}/reviews/eligibility', [ReviewController::class, 'eligibility']);
    Route::post('/products/{product}/reviews', [ReviewController::class, 'store']);

    Route::get('/designs', [DesignController::class, 'index']);
    Route::post('/designs/{design}/approve', [DesignController::class, 'approve']);
    Route::post('/designs/{design}/reject', [DesignController::class, 'reject']);

    Route::get('/system-events', [SystemEventController::class, 'index']);
    Route::get('/agent-statuses', [AgentStatusController::class, 'index']);
    Route::patch('/agent-statuses/{agentStatus}', [AgentStatusController::class, 'update']);

    Route::get('/activity', [ActivityController::class, 'index']);
    Route::get('/project-tasks', [ProjectTaskController::class, 'index']);

    Route::patch('/site-settings', [SiteSettingController::class, 'update']);

    Route::get('/epics', [EpicController::class, 'index']);
    Route::post('/epics/{epic}/approve', [EpicController::class, 'approve']);
    Route::post('/epics/{epic}/reject', [EpicController::class, 'reject']);
    Route::post('/epics/{epic}/delay', [EpicController::class, 'delay']);

    Route::get('/visioner-chat', [VisionerChatController::class, 'index']);
    Route::post('/visioner-chat', [VisionerChatController::class, 'store'])->middleware('throttle:visioner-chat');
});
