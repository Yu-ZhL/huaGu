<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/products', [App\Http\Controllers\ProductController::class, 'index']);
    Route::post('/products/export', [App\Http\Controllers\ProductController::class, 'export']);
    Route::post('/products/batch-calculate', [App\Http\Controllers\ProductController::class, 'batchCalculate']);
    // 其他 API 端点...
});

Route::post('/login', [App\Http\Controllers\AuthController::class, 'login']);
