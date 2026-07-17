<?php

use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\AgentStatusController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\DesignController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PayPalWebhookController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SystemEventController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:register');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product}', [ProductController::class, 'show']);

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

    Route::get('/designs', [DesignController::class, 'index']);
    Route::post('/designs/{design}/approve', [DesignController::class, 'approve']);
    Route::post('/designs/{design}/reject', [DesignController::class, 'reject']);

    Route::get('/system-events', [SystemEventController::class, 'index']);
    Route::get('/agent-statuses', [AgentStatusController::class, 'index']);
    Route::patch('/agent-statuses/{agentStatus}', [AgentStatusController::class, 'update']);

    Route::get('/activity', [ActivityController::class, 'index']);
});
