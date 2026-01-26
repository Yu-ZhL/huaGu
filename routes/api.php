<?php

use Illuminate\Support\Facades\Route;

Route::post('/login', [App\Http\Controllers\AuthController::class, 'login']);

Route::get('/vip/plans', [App\Http\Controllers\Api\VipController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/products', [App\Http\Controllers\ProductController::class, 'index']);
    Route::post('/products/export', [App\Http\Controllers\ProductController::class, 'export']);
    Route::post('/products/batch-calculate', [App\Http\Controllers\ProductController::class, 'batchCalculate']);

    Route::get('/vip/my-info', [App\Http\Controllers\Api\VipController::class, 'myVipInfo']);
    Route::get('/vip/my-orders', [App\Http\Controllers\Api\VipController::class, 'myOrders']);
    Route::get('/vip/my-ai-points', [App\Http\Controllers\Api\VipController::class, 'myAiPoints']);

    Route::post('/orders', [App\Http\Controllers\Api\OrderController::class, 'create']);
    Route::get('/orders/{orderNo}', [App\Http\Controllers\Api\OrderController::class, 'show']);
    Route::post('/orders/{orderNo}/cancel', [App\Http\Controllers\Api\OrderController::class, 'cancel']);
    Route::get('/orders/{orderNo}/payment-status', [App\Http\Controllers\Api\OrderController::class, 'queryPaymentStatus']);

    Route::post('/payment/alipay/create', [App\Http\Controllers\Api\PaymentController::class, 'createAlipay']);

    Route::post('/coupons/validate', [App\Http\Controllers\Api\CouponController::class, 'validate']);
});

Route::post('/payment/alipay-notify', [App\Http\Controllers\Api\PaymentController::class, 'alipayNotify']);
