<?php

use App\Http\Controllers\Api\ImageSearch\Platform1688Controller;
use App\Http\Controllers\Api\ImageSearch\Platform1688CrossBorderController;
use App\Http\Controllers\Api\ImageSearch\Platform1688ProController;
use App\Http\Controllers\Api\ImageSearch\PlatformTaobaoController;
use App\Http\Controllers\Api\ImageSearch\PlatformTaobaoLiteController;
use App\Http\Controllers\Api\ImageSearch\PlatformAlibabaController;
use App\Http\Controllers\Api\ImageSearch\PlatformAmazonController;
use App\Http\Controllers\Api\ImageSearch\PlatformAmazonLiteController;
use App\Http\Controllers\Api\ImageSearch\PlatformAliExpressController;
use App\Http\Controllers\Api\ImageSearch\PlatformAliExpressProController;
use App\Http\Controllers\Api\ImageSearch\PlatformSheinController;
use App\Http\Controllers\Api\ImageSearch\PlatformOzonController;
use App\Http\Controllers\Api\ImageSearch\PlatformNaverShoppingController;
use App\Http\Controllers\Api\ImageSearch\PlatformEbayController;
use App\Http\Controllers\Api\ImageSearch\PlatformGoogleLensController;
use App\Http\Controllers\Api\ImageSearch\PlatformBingController;
use App\Http\Controllers\Api\ImageSearch\PlatformBaiduController;
use App\Http\Controllers\Api\ImageSearch\PlatformYandexController;
use App\Http\Controllers\Api\ImageSearch\PlatformWildberriesController;
use App\Http\Controllers\Api\ImageSearch\PlatformDomeggookController;
use App\Http\Controllers\Api\ImageSearch\PlatformCoupangController;
use App\Http\Controllers\Api\ImageSearch\PlatformNetseaController;
use App\Http\Controllers\Api\ImageSearch\PlatformOwnerclanController;
use App\Http\Controllers\Api\ImageSearch\PlatformOnch3Controller;
use App\Http\Controllers\Api\ImageSearch\PlatformMercariController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| 搜同款 API 路由
|--------------------------------------------------------------------------
|
| 所有平台的图片搜索接口路由配置
| 路由格式: /api/image-search/{platform}/{method}
|
*/

// 预留中间件配置
// Route::middleware(['image-search-auth'])->group(function () {

// 1688 平台
Route::prefix('api/image-search/1688')->name('api.image-search.1688.')->group(function () {
    Route::post('/image', [Platform1688Controller::class, 'searchByImage'])->name('image');
    Route::post('/url', [Platform1688Controller::class, 'searchByUrl'])->name('url');
});

// 1688 跨境平台
Route::prefix('api/image-search/1688-cross-border')->name('api.image-search.1688-cross-border.')->group(function () {
    Route::post('/image', [Platform1688CrossBorderController::class, 'searchByImage'])->name('image');
    Route::post('/url', [Platform1688CrossBorderController::class, 'searchByUrl'])->name('url');
});

// 1688 PRO 平台
Route::prefix('api/image-search/1688-pro')->name('api.image-search.1688-pro.')->group(function () {
    Route::post('/image', [Platform1688ProController::class, 'searchByImage'])->name('image');
    Route::post('/url', [Platform1688ProController::class, 'searchByUrl'])->name('url');
});

// 淘宝平台
Route::prefix('api/image-search/taobao')->name('api.image-search.taobao.')->group(function () {
    Route::post('/image', [PlatformTaobaoController::class, 'searchByImage'])->name('image');
    Route::post('/url', [PlatformTaobaoController::class, 'searchByUrl'])->name('url');
});

// Taobao Lite 平台
Route::prefix('api/image-search/taobao-lite')->name('api.image-search.taobao-lite.')->group(function () {
    Route::post('/image', [PlatformTaobaoLiteController::class, 'searchByImage'])->name('image');
    Route::post('/url', [PlatformTaobaoLiteController::class, 'searchByUrl'])->name('url');
});

// Alibaba.com 平台
Route::prefix('api/image-search/alibaba')->name('api.image-search.alibaba.')->group(function () {
    Route::post('/image', [PlatformAlibabaController::class, 'searchByImage'])->name('image');
    Route::post('/url', [PlatformAlibabaController::class, 'searchByUrl'])->name('url');
});

// Amazon 平台
Route::prefix('api/image-search/amazon')->name('api.image-search.amazon.')->group(function () {
    Route::post('/image', [PlatformAmazonController::class, 'searchByImage'])->name('image');
    Route::post('/url', [PlatformAmazonController::class, 'searchByUrl'])->name('url');
});

// Amazon Lite 平台
Route::prefix('api/image-search/amazon-lite')->name('api.image-search.amazon-lite.')->group(function () {
    Route::post('/image', [PlatformAmazonLiteController::class, 'searchByImage'])->name('image');
    Route::post('/url', [PlatformAmazonLiteController::class, 'searchByUrl'])->name('url');
});

// 速卖通平台
Route::prefix('api/image-search/aliexpress')->name('api.image-search.aliexpress.')->group(function () {
    Route::post('/image', [PlatformAliExpressController::class, 'searchByImage'])->name('image');
    Route::post('/url', [PlatformAliExpressController::class, 'searchByUrl'])->name('url');
});

// AliExpress Pro 平台
Route::prefix('api/image-search/aliexpress-pro')->name('api.image-search.aliexpress-pro.')->group(function () {
    Route::post('/image', [PlatformAliExpressProController::class, 'searchByImage'])->name('image');
    Route::post('/url', [PlatformAliExpressProController::class, 'searchByUrl'])->name('url');
});

// SHEIN 平台
Route::prefix('api/image-search/shein')->name('api.image-search.shein.')->group(function () {
    Route::post('/image', [PlatformSheinController::class, 'searchByImage'])->name('image');
    Route::post('/url', [PlatformSheinController::class, 'searchByUrl'])->name('url');
});

// Ozon 平台
Route::prefix('api/image-search/ozon')->name('api.image-search.ozon.')->group(function () {
    Route::post('/image', [PlatformOzonController::class, 'searchByImage'])->name('image');
    Route::post('/url', [PlatformOzonController::class, 'searchByUrl'])->name('url');
});

// Naver Shopping 平台
Route::prefix('api/image-search/naver-shopping')->name('api.image-search.naver-shopping.')->group(function () {
    Route::post('/image', [PlatformNaverShoppingController::class, 'searchByImage'])->name('image');
    Route::post('/url', [PlatformNaverShoppingController::class, 'searchByUrl'])->name('url');
});

// eBay 平台
Route::prefix('api/image-search/ebay')->name('api.image-search.ebay.')->group(function () {
    Route::post('/image', [PlatformEbayController::class, 'searchByImage'])->name('image');
    Route::post('/url', [PlatformEbayController::class, 'searchByUrl'])->name('url');
});

// Google Lens 平台
Route::prefix('api/image-search/google-lens')->name('api.image-search.google-lens.')->group(function () {
    Route::post('/image', [PlatformGoogleLensController::class, 'searchByImage'])->name('image');
    Route::post('/url', [PlatformGoogleLensController::class, 'searchByUrl'])->name('url');
});

// 必应平台
Route::prefix('api/image-search/bing')->name('api.image-search.bing.')->group(function () {
    Route::post('/image', [PlatformBingController::class, 'searchByImage'])->name('image');
    Route::post('/url', [PlatformBingController::class, 'searchByUrl'])->name('url');
});

// 百度平台
Route::prefix('api/image-search/baidu')->name('api.image-search.baidu.')->group(function () {
    Route::post('/image', [PlatformBaiduController::class, 'searchByImage'])->name('image');
    Route::post('/url', [PlatformBaiduController::class, 'searchByUrl'])->name('url');
});

// Yandex 平台
Route::prefix('api/image-search/yandex')->name('api.image-search.yandex.')->group(function () {
    Route::post('/image', [PlatformYandexController::class, 'searchByImage'])->name('image');
    Route::post('/url', [PlatformYandexController::class, 'searchByUrl'])->name('url');
});

// Wildberries 平台
Route::prefix('api/image-search/wildberries')->name('api.image-search.wildberries.')->group(function () {
    Route::post('/image', [PlatformWildberriesController::class, 'searchByImage'])->name('image');
    Route::post('/url', [PlatformWildberriesController::class, 'searchByUrl'])->name('url');
});

// Domeggook 平台
Route::prefix('api/image-search/domeggook')->name('api.image-search.domeggook.')->group(function () {
    Route::post('/image', [PlatformDomeggookController::class, 'searchByImage'])->name('image');
    Route::post('/url', [PlatformDomeggookController::class, 'searchByUrl'])->name('url');
});

// Coupang 平台
Route::prefix('api/image-search/coupang')->name('api.image-search.coupang.')->group(function () {
    Route::post('/image', [PlatformCoupangController::class, 'searchByImage'])->name('image');
    Route::post('/url', [PlatformCoupangController::class, 'searchByUrl'])->name('url');
});

// Netsea 平台
Route::prefix('api/image-search/netsea')->name('api.image-search.netsea.')->group(function () {
    Route::post('/image', [PlatformNetseaController::class, 'searchByImage'])->name('image');
    Route::post('/url', [PlatformNetseaController::class, 'searchByUrl'])->name('url');
});

// Ownerclan 平台
Route::prefix('api/image-search/ownerclan')->name('api.image-search.ownerclan.')->group(function () {
    Route::post('/image', [PlatformOwnerclanController::class, 'searchByImage'])->name('image');
    Route::post('/url', [PlatformOwnerclanController::class, 'searchByUrl'])->name('url');
});

// Onch3 平台
Route::prefix('api/image-search/onch3')->name('api.image-search.onch3.')->group(function () {
    Route::post('/image', [PlatformOnch3Controller::class, 'searchByImage'])->name('image');
    Route::post('/url', [PlatformOnch3Controller::class, 'searchByUrl'])->name('url');
});

// Mercari 平台
Route::prefix('api/image-search/mercari')->name('api.image-search.mercari.')->group(function () {
    Route::post('/image', [PlatformMercariController::class, 'searchByImage'])->name('image');
    Route::post('/url', [PlatformMercariController::class, 'searchByUrl'])->name('url');
});

// });
