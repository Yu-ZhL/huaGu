<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\VipController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\CouponController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\TemuProductController;
use App\Http\Controllers\Api\FreightConfigController;

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

    // 验证优惠码
    Route::match(['post', 'get'], '/coupons/{code}/validate', [CouponController::class, 'check']);

    // Temu 商品管理
    Route::prefix('temu/products')->group(function () {
        Route::get('/', [TemuProductController::class, 'index']);
        Route::get('/simple', [TemuProductController::class, 'indexSimple']);
        Route::get('/{id}', [TemuProductController::class, 'show']);
        Route::post('/collect-similar', [TemuProductController::class, 'collectSimilar']);
        Route::get('/{productId}/sources', [TemuProductController::class, 'getSources']);
        Route::post('/set-primary-source', [TemuProductController::class, 'setPrimarySource']);
        Route::post('/calculate-profit', [TemuProductController::class, 'calculateProfit']);
        Route::post('/{id}/remark', [TemuProductController::class, 'updateRemark']);
        Route::delete('/{id}', [TemuProductController::class, 'destroy']);
    });

    // 运费配置
    Route::get('/freight-config', [FreightConfigController::class, 'show']);
    Route::put('/freight-config', [FreightConfigController::class, 'update']);

    // 飞猫选品 - 采集
    Route::post('/feimao/products', [App\Http\Controllers\Api\FeimaoProductController::class, 'index']);
});

// 支付回调 - 不需要认证
Route::post('/payment/alipay-notify', [PaymentController::class, 'alipayNotify']);


Route::post('/feimao/categories', [App\Http\Controllers\Api\FeimaoProductController::class, 'getCategories']);
Route::post('/feimao/products/list', [App\Http\Controllers\Api\FeimaoProductController::class, 'getCategoryProducts']);
Route::post('/feimao/sales-records', [App\Http\Controllers\Api\FeimaoProductController::class, 'getSalesRecord']);

// 系统配置
Route::get('/settings/customer-service', [SettingController::class, 'getCustomerService']);
Route::get('/settings/usage-tutorial', [SettingController::class, 'getUsageTutorial']);
Route::get('/settings/extension-download', [SettingController::class, 'downloadExtension']);
