<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    try {
        $config = config('database.redis.default');
        $host = $config['host'] ?? '未设置';
        $port = $config['port'] ?? '未设置';
        // 简单的密码掩码处理，只显示前两位和长度
        $password = $config['password'] ?? null;
        // 简单的密码掩码处理，只显示前两位和长度
        // $passwordMasked = $password ? (substr($password, 0, 2) . '*** (len=' . strlen($password) . ')') : '未设置/Null';
        $passwordMasked = $password;

        $msg = "正在尝试连接 Redis...<br>";
        $msg .= "配置 Host: {$host}, Port: {$port}, Password: {$passwordMasked}<br>";

        $redis = Illuminate\Support\Facades\Redis::connection();

        // 尝试 PING
        try {
            $redis->ping();
            $msg .= "Laravel Facade PING 成功。<br>";
        } catch (\Exception $e) {
            $msg .= "Laravel Facade PING 失败: " . $e->getMessage() . "<br>";
        }

        $msg .= "<hr><strong>尝试原生 Redis 类连接测试 (绕过 Laravel):</strong><br>";
        if (class_exists('Redis')) {
            try {
                $nativeRedis = new \Redis();
                $connected = $nativeRedis->connect($host, (int) $port, 2.5); // 2.5s timeout
                if ($connected) {
                    $msg .= "原生 connect 成功。<br>";
                    if ($password) {
                        // 注意：如果密码错误，auth 会抛出异常还是返回 false 取决于 phpredis 版本
                        $authRes = $nativeRedis->auth($password);
                        $msg .= "原生 auth 结果: " . ($authRes ? 'true' : 'false') . "<br>";
                    }
                    $msg .= "原生 ping: " . $nativeRedis->ping() . "<br>";
                } else {
                    $msg .= "原生 connect 返回 false。<br>";
                }
            } catch (\Exception $ne) {
                $msg .= "原生 Redis 异常: " . $ne->getMessage() . "<br>";
            }
        } else {
            $msg .= "错误: Redis 类不存在 (php-redis 扩展未加载?)。<br>";
        }
        $msg .= "Redis 扩展版本: " . phpversion('redis') . "<br>";

        // 重新抛出异常以显示之前的详细错误面板
        if (strpos($msg, "Laravel Facade PING 失败") !== false) {
            throw new \Exception("Laravel Redis 连接最终失败", 500);
        }

        $redis->set('test_redis_key', 'Redis 连接成功! ' . date('Y-m-d H:i:s'));
        $value = $redis->get('test_redis_key');

        return response($msg . "写入/读取测试成功: " . $value);
    } catch (\Exception $e) {
        $errorDetail = "<h3>Redis 连接失败</h3>";
        $errorDetail .= "<strong>错误信息:</strong> " . $e->getMessage() . "<br>";
        $errorDetail .= "<strong>错误代码:</strong> " . $e->getCode() . "<br>";
        $errorDetail .= "<strong>异常类型:</strong> " . get_class($e) . "<br>";
        $errorDetail .= "<strong>文件/行号:</strong> " . $e->getFile() . ":" . $e->getLine() . "<br>";

        // 如果是 Predis 连接异常，通常包含更底层的错误
        if ($e->getPrevious()) {
            $errorDetail .= "<strong>底层错误:</strong> " . $e->getPrevious()->getMessage() . "<br>";
        }

        // 显示当前加载的配置以便核对
        $config = config('database.redis.default');
        $errorDetail .= "<hr><strong>当前使用的配置 (database.redis.default):</strong><br>";
        $errorDetail .= "<pre>" . json_encode([
            'host' => $config['host'] ?? 'N/A',
            'port' => $config['port'] ?? 'N/A',
            'password' => $config['password'] ?? 'NULL',
            'database' => $config['database'] ?? 'N/A',
            'username' => $config['username'] ?? 'NULL',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";

        return response($errorDetail, 500);
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
