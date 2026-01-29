<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    $output = "<html><body style='font-family:sans-serif; padding:20px;'>";
    $output .= "<h2>Redis 连接诊断报告 (Deep Debug Mode)</h2>";
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

    // 2. 原生 Redis 类测试 (phpredis)
    $output .= "<h3>2. 原生 Redis 类测试 (phpredis)</h3>";
    if (class_exists('Redis')) {
        $output .= "<div>扩展版本: " . phpversion('redis') . "</div>";
        $nativeRedis = new \Redis();
        try {
            $t1 = microtime(true);

            // Test 1: 类型严格测试 - 模拟 Config 传入 String 端口
            $portVal = $port;
            $output .= "<div>尝试连接: Host={$host}, Port=" . var_export($portVal, true) . " (Type: " . gettype($portVal) . "), Timeout=5...</div>";

            // 注意：这里我们故意不转 int，看看是否与之前行为一致，且使用 Laravel 的默认参数
            // connect(host, port, timeout, reserved, retry, read_timeout)
            $connected = $nativeRedis->connect($host, $portVal, 5, null, 0, 0);

            $t2 = microtime(true);

            if ($connected) {
                $output .= "<div style='color:green'>✔ 原生连接成功 (耗时 " . round(($t2 - $t1) * 1000, 2) . "ms)</div>";

                if ($password) {
                    if ($nativeRedis->auth($password)) {
                        $output .= "<div>认证成功</div>";
                    } else {
                        $output .= "<div style='color:red'>认证失败</div>";
                    }
                }

                // 尝试切库
                if (isset($config['database'])) {
                    $nativeRedis->select((int) $config['database']);
                }

                try {
                    $ping = $nativeRedis->ping();
                    $output .= "<div style='color:green'>✔ PING: {$ping}</div>";
                } catch (\Exception $e) {
                    $output .= "<div style='color:red'>✘ PING 异常: " . $e->getMessage() . "</div>";
                }
                $nativeRedis->close();

            } else {
                $output .= "<div style='color:red'>✘ 原生 connect 返回 false</div>";
            }
        } catch (\Exception $e) {
            $output .= "<div style='color:red'>✘ 原生测试异常: " . $e->getMessage() . "</div>";
        }
    } else {
        $output .= "<div style='color:red'>✘ 没找到 Redis 类</div>";
    }

    // 2.5 Illuminate PhpRedisConnector 直接测试
    $output .= "<h3>2.5 Illuminate PhpRedisConnector 直接测试</h3>";
    try {
        $connector = new \Illuminate\Redis\Connectors\PhpRedisConnector();
        $output .= "<div>实例化 Connector 成功</div>";

        // 构造 Laravel 传递给 Connector 的标准配置数组
        $laravelConfig = [
            'host' => $host,
            'port' => $port,
            'password' => $password,
            'database' => $config['database'] ?? 0,
            'timeout' => 5,
            'read_write_timeout' => 0,
            'persistent' => false,
            'name' => 'default',
        ];

        // 如果 config 有 options，也加上
        if (isset($config['options'])) {
            $output .= "<div>Debug: Config has options: " . json_encode($config['options']) . "</div>";
        }

        $output .= "<div>尝试使用 Connector->connect()...</div>";
        // 注意：Laravel 11 connect 签名可能是 (config, options)
        $redisAdapter = $connector->connect($laravelConfig, $config['options'] ?? []);
        $output .= "<div style='color:green'>✔ Connector->connect() 成功返回对象</div>";

        // 尝试操作
        $redisAdapter->set('test_connector', 'Hello');
        $output .= "<div>写入测试成功</div>";

    } catch (\Exception $e) {
        $output .= "<div style='color:red'>✘ Connector 测试失败: " . $e->getMessage() . "</div>";
        $output .= "<div>Error File: " . $e->getFile() . ":" . $e->getLine() . "</div>";
        // 打印特定的 Trace 信息，看是不是在 connect 内部挂的
    }

    // 3. Laravel Facade 测试
    $output .= "<h3>3. Laravel Facade 测试</h3>";
    try {
        // 强制清除之前的实例
        Illuminate\Support\Facades\Redis::clearResolvedInstances();
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
