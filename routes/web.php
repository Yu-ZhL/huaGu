<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    try {
        Illuminate\Support\Facades\Redis::connection()->set('test_redis_key', 'Redis 连接成功! ' . date('Y-m-d H:i:s'));
        $value = Illuminate\Support\Facades\Redis::connection()->get('test_redis_key');
        return response("Redis 测试成功: " . $value);
    } catch (\Exception $e) {
        return response("Redis 连接失败: " . $e->getMessage(), 500);
    }
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
require __DIR__ . '/image_search.php';
require __DIR__ . '/auth_api.php';
