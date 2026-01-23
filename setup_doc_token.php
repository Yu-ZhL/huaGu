<?php
use Illuminate\Contracts\Console\Kernel;
use App\Models\User;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

// 确保测试用户存在
$user = User::firstOrCreate(
    ['phone' => '13800000000'],
    [
        'phone_area_code' => '86',
        'password' => '$2y$12$K.1.1.1.1.1.1.1.1.1.1e.1.1.1.1.1.1.1.1.1.1.1', // 模拟哈希密码
        'name' => 'API Tester'
    ]
);

// 清理旧 Token
$user->tokens()->where('name', 'scribe_test_token')->delete();

// 生成新 Token
$token = $user->createToken('scribe_test_token')->plainTextToken;

echo "TOKEN:" . $token;
