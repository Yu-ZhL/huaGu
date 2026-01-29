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
        $output .= "<div>扩展版本: " . phpversion('redis') . "</div>";
        $nativeRedis = new \Redis();
        try {
            $t1 = microtime(true);

            // 模拟 Laravel 的连接参数：Host, Port, Timeout(5), Reserved(null), Retry(0), ReadTimeout(0)
            // config('database.redis.default.read_write_timeout') 是 0
            $connected = $nativeRedis->connect($host, (int) $port, 5, null, 0, 0);

            $t2 = microtime(true);

            if ($connected) {
                $output .= "<div style='color:green'>✔ 连接成功 (耗时 " . round(($t2 - $t1) * 1000, 2) . "ms)</div>";

                if ($password) {
                    // 尝试认证
                    if ($nativeRedis->auth($password)) {
                        $output .= "<div>认证成功</div>";
                    } else {
                        $output .= "<div style='color:red'>认证失败</div>";
                    }
                }

                try {
                    $ping = $nativeRedis->ping();
                    $output .= "<div style='color:green'>✔ PING 响应: {$ping}</div>";
                } catch (\Exception $e) {
                    $output .= "<div style='color:red'>✘ PING 异常: " . $e->getMessage() . "</div>";
                }

            } else {
                $output .= "<div style='color:red'>✘ connect 返回 false</div>";
            }
        } catch (\Exception $e) {
            $output .= "<div style='color:red'>✘ 原生测试异常: " . $e->getMessage() . "</div>";
        }
    } else {
        $output .= "<div style='color:red'>✘ 没找到 Redis 类</div>";
    }

    // 3. Laravel Facade 测试
    $output .= "<h3>3. Laravel Facade 测试</h3>";
    try {
        $redis = Illuminate\Support\Facades\Redis::connection();
        $redis->ping();
        $output .= "<div style='color:green'>✔ Laravel Facade PING 成功</div>";

        $redis->set('test_laravel_key', 'ok_' . time());
        $val = $redis->get('test_laravel_key');
        $output .= "<div>读写测试值: {$val}</div>";

    } catch (\Exception $e) {
        $output .= "<div style='color:red'>✘ Laravel Facade 失败: " . $e->getMessage() . "</div>";
        $output .= "<div style='background:#f5f5f5;padding:10px;margin-top:5px;border-radius:4px;font-family:monospace;'>" . $e->getFile() . ":" . $e->getLine() . "</div>";

        // 出错时打印配置
        $output .= "<hr><strong>配置 Dump:</strong><pre>" . json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
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
