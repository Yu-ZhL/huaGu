<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| 用户认证 API 路由
|--------------------------------------------------------------------------
|
| 前端用户的注册、登录、验证码等功能路由
|
*/

Route::prefix('api/auth')->name('api.auth.')->group(function () {
    Route::post('/send-code', [AuthController::class, 'sendCode'])->name('send-code');
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('reset-password');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me'])->name('me');
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    });
});
