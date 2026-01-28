<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\VipController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\CouponController;

// 认证路由
Route::post('/auth/send-code', [AuthController::class, 'sendCode']);
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

// VIP套餐 - 不需要认证
Route::get('/vip/plans', [VipController::class, 'index']);

// 需要认证的路由
Route::middleware('auth:sanctum')->group(function () {
    // 认证用户信息
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // VIP信息
    Route::get('/vip/my-info', [VipController::class, 'myVipInfo']);
    Route::get('/vip/my-orders', [VipController::class, 'myOrders']);
    Route::get('/vip/my-ai-points', [VipController::class, 'myAiPoints']);

    // 订单
    Route::post('/orders', [OrderController::class, 'create']);
    Route::get('/orders/{orderNo}', [OrderController::class, 'show']);
    Route::post('/orders/{orderNo}/cancel', [OrderController::class, 'cancel']);
    Route::get('/orders/{orderNo}/payment-status', [OrderController::class, 'queryPaymentStatus']);

    // 支付
    Route::post('/payment/alipay/create', [PaymentController::class, 'createAlipay']);

    // 优惠码
    // 优惠码验证
    // 注意：这里必须用 match 或 any，或者确保请求方法严格匹配。
    // 之前的坑：Guzzle 请求如果遇到 301 跳转可能会把 POST 变 GET，导致 MethodNotAllowed。
    // 如果再报错，检查下是不是客户端自动跳转了，或者路由缓存没清干净。
    Route::post('/coupons/{code}/validate', [CouponController::class, 'check']);
});

// 支付回调 - 不需要认证
Route::post('/payment/alipay-notify', [PaymentController::class, 'alipayNotify']);

// 飞猫选品
use App\Http\Controllers\Api\FeimaoProductController;
Route::post('/feimao/products', [FeimaoProductController::class, 'index']);
