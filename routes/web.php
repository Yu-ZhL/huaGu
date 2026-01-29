<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Redis\RedisManager;

Route::get('/', function () {
    $output = "<html><body style='font-family:sans-serif; padding:20px; line-height:1.6'>";
    $output .= "<h2>Redis 终极诊断报告</h2>";
    $output .= "<p>时间: " . date('Y-m-d H:i:s') . "</p>";

    // --- 0. 环境与配置检测 ---
    $output .= "<h3>0. 环境与配置</h3>";
    $extVer = phpversion('redis');
    $output .= "<div>Redis 扩展版本: " . ($extVer ?: '<span style="color:red">未安装</span>') . "</div>";

    // 获取所有相关配置
    $dbConfig = config('database.redis');
    $output .= "<details><summary><strong>完整 Redis 配置 (点击展开)</strong></summary><pre style='background:#f4f4f4;padding:10px;'>" . json_encode($dbConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre></details>";

    // --- 1. 原生基准测试 (Control Group) ---
    $output .= "<h3>1. 原生 Redis 基准测试</h3>";
    $host = $dbConfig['default']['host'];
    $port = $dbConfig['default']['port'];
    $pwd = $dbConfig['default']['password'];

    $native = new \Redis();
    try {
        // 严格模拟 Laravel 的参数
        $connected = $native->connect($host, $port, 5, null, 0, 0);
        if ($connected) {
            $output .= "<div style='color:green'>✔ Socket 连接成功</div>";
            if ($pwd) {
                if ($native->auth($pwd)) {
                    $output .= "<div style='color:green'>✔ Auth 认证成功</div>";
                } else {
                    $output .= "<div style='color:red'>✘ Auth 认证失败</div>";
                }
            }
            $pong = $native->ping();
            $output .= "<div style='color:green'>✔ Ping 响应: {$pong}</div>";
        } else {
            $output .= "<div style='color:red'>✘ Socket 连接失败</div>";
        }
        $native->close();
    } catch (\Throwable $e) {
        $output .= "<div style='color:red'>✘ 原生测试抛出异常: " . $e->getMessage() . "</div>";
    }

    // --- 2. 手动构建 RedisManager 测试 (Isolation Test) ---
    // 这个测试用于判断是 Facade/Container 单例污染，还是 RedisManager 本身逻辑与环境不兼容
    $output .= "<h3>2. 手动构建 RedisManager (隔离测试)</h3>";
    try {
        $driver = $dbConfig['client'];
        $configArr = $dbConfig;

        // 手动实例化 Manager，不经过 Laravel IOC 容器
        $manager = new RedisManager(app(), $driver, $configArr);

        // 尝试获取连接
        $conn = $manager->connection('default');
        $output .= "<div>Manager->connection() 获取成功</div>";

        // 尝试 Ping
        $res = $conn->ping();
        $output .= "<div style='color:green'>✔ Manager 新实例 Ping 成功: {$res}</div>";

        // 尝试读写
        $conn->set('debug_mgr_key', 'works');
        $output .= "<div>Manager 读写测试: " . $conn->get('debug_mgr_key') . "</div>";

        // 检查实际的 Client 对象
        $client = $conn->client();
        $output .= "<div>底层 Client 对象: " . get_class($client) . "</div>";

    } catch (\Throwable $e) {
        $output .= "<div style='color:red'>✘ 手动 Manager 测试失败: " . $e->getMessage() . "</div>";
        $output .= "<div style='font-size:12px;color:#666'>File: " . $e->getFile() . ":" . $e->getLine() . "</div>";
    }

    // --- 3. Facade 诊断与修复尝试 (Facade Test) ---
    $output .= "<h3>3. Laravel Facade 诊断</h3>";
    try {
        // 3.1 原始状态测试
        try {
            Illuminate\Support\Facades\Redis::connection('default')->ping();
            $output .= "<div style='color:green'>✔ 原始 Facade Ping 成功 (奇迹!)</div>";
        } catch (\Throwable $e) {
            $output .= "<div style='color:red'>✘ 原始 Facade Ping 失败: " . $e->getMessage() . "</div>";

            // 3.2 尝试从错误中恢复：清除所有已解析的实例
            $output .= "<div>尝试清除 Facade 缓存重连...</div>";
            Illuminate\Support\Facades\Redis::clearResolvedInstances(); // 清除 Facade 缓存
            app()->forgetInstance('redis'); // 清除容器绑定
            app()->register(\Illuminate\Redis\RedisServiceProvider::class, true); // 重新注册 Provider

            try {
                Illuminate\Support\Facades\Redis::connection('default')->ping();
                $output .= "<div style='color:green'>✔ 重置 Provider 后 Ping 成功 (说明是单例污染或初始化时机问题)</div>";
            } catch (\Throwable $e2) {
                $output .= "<div style='color:red'>✘ 重置后依然失败: " . $e2->getMessage() . "</div>";
            }
        }
    } catch (\Throwable $e) {
        $output .= "<div>Facade 测试发生严重错误: " . $e->getMessage() . "</div>";
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

    // Feimao Cache Management
    Route::get('/feimao/cache', [\App\Http\Controllers\FeimaoCacheController::class, 'index'])->name('feimao.cache');
    Route::delete('/feimao/cache', [\App\Http\Controllers\FeimaoCacheController::class, 'destroy'])->name('feimao.cache.destroy');
    Route::post('/feimao/cache/refresh', [\App\Http\Controllers\FeimaoCacheController::class, 'refresh'])->name('feimao.cache.refresh');
});

require __DIR__ . '/auth.php';
require __DIR__ . '/image_search.php';
require __DIR__ . '/auth_api.php';
