<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    $output = "<html><body style='font-family:sans-serif; padding:20px;'>";
    $output .= "<h2>Redis 连接诊断报告 (Debug Mode)</h2>";
    $output .= "<p>时间: " . date('Y-m-d H:i:s') . "</p>";

    // 1. 获取配置
    $config = config('database.redis.default');
    $host = $config['host'] ?? '未设置';
    $port = $config['port'] ?? '未设置';
    $password = $config['password'] ?? null;
    $output .= "<h3>1. 配置信息</h3>";
    $output .= "<ul>";
    $output .= "<li>Host: <strong>{$host}</strong></li>";
    $output .= "<li>Port: <strong>{$port}</strong></li>";
    $output .= "<li>Password: <strong>" . ($password ?? 'NULL') . "</strong></li>";
    $output .= "</ul>";

    // 2. 原生 Redis 类测试
    $output .= "<h3>2. 原生 Redis 类测试 (phpredis)</h3>";
    if (class_exists('Redis')) {
        $output .= "<div>Redis 扩展版本: " . phpversion('redis') . "</div>";
        $nativeRedis = new \Redis();
        try {
            $t1 = microtime(true);
            $connected = $nativeRedis->connect($host, (int) $port, 2.5); // 2.5s timeout
            $t2 = microtime(true);

            if ($connected) {
                $output .= "<div style='color:green'>✔ 原生 connect 成功 (耗时 " . round(($t2 - $t1) * 1000, 2) . "ms)</div>";

                if ($password) {
                    $authRes = $nativeRedis->auth($password);
                    $output .= "<div>原生 auth 结果: " . ($authRes ? 'true' : 'false') . "</div>";
                }

                try {
                    $ping = $nativeRedis->ping();
                    $output .= "<div style='color:green'>✔ 原生 PING 响应: {$ping}</div>";
                } catch (\Exception $e) {
                    $output .= "<div style='color:red'>✘ 原生 PING 异常: " . $e->getMessage() . "</div>";
                }

            } else {
                $output .= "<div style='color:red'>✘ 原生 connect 返回 false</div>";
            }
        } catch (\Exception $e) {
            $output .= "<div style='color:red'>✘ 原生测试发生异常: " . $e->getMessage() . "</div>";
        }
    } else {
        $output .= "<div style='color:red'>✘ 错误: Redis 类不存在</div>";
    }

    // 3. Laravel Facade 测试
    $output .= "<h3>3. Laravel Facade 测试</h3>";
    try {
        $redis = Illuminate\Support\Facades\Redis::connection();
        $redis->ping();
        $output .= "<div style='color:green'>✔ Laravel Facade PING 成功</div>";

        $redis->set('test_laravel_key', 'ok_' . time());
        $val = $redis->get('test_laravel_key');
        $output .= "<div>Laravel 读写测试: {$val}</div>";

    } catch (\Exception $e) {
        $output .= "<div style='color:red'>✘ Laravel Facade 连接失败</div>";
        $output .= "<pre style='background:#eee;padding:10px;'>" . $e->getMessage() . "</pre>";
        $output .= "<div>文件: " . $e->getFile() . ":" . $e->getLine() . "</div>";

        // 只有在 Laravel 失败时才显示详细配置 dump
        $output .= "<hr><strong>完整配置 Dump:</strong><pre>" . json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
    }

    $output .= "</body></html>";
    return response($output);
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
