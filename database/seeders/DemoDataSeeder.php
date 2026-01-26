<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Order;
use App\Models\VipPlan;
use App\Models\UserAiPoint;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // 创建测试用户
        $users = [
            [
                'name' => '测试用户1',
                'phone' => '13800138001',
                'password' => Hash::make('123456'),
                'status' => 1,
                'register_ip' => '127.0.0.1',
            ],
            [
                'name' => '测试用户2',
                'phone' => '13800138002',
                'password' => Hash::make('123456'),
                'status' => 1,
                'register_ip' => '127.0.0.1',
            ],
            [
                'name' => '测试VIP用户',
                'phone' => '13800138003',
                'password' => Hash::make('123456'),
                'vip_level_id' => 1,
                'vip_expired_at' => now()->addDays(30),
                'status' => 1,
                'register_ip' => '127.0.0.1',
            ],
        ];

        foreach ($users as $userData) {
            $user = User::updateOrCreate(
                ['phone' => $userData['phone']],
                $userData
            );

            // 给每个用户一些初始点数
            if ($user->ai_points == 0) {
                UserAiPoint::addPoints(
                    $user->id,
                    1000,
                    UserAiPoint::TYPE_GIFT,
                    '新用户注册赠送'
                );
            }
        }

        // 创建一些测试订单
        $vipPlans = VipPlan::all();
        $testUser = User::where('phone', '13800138001')->first();

        if ($testUser && $vipPlans->count() > 0) {
            // 已支付订单
            Order::create([
                'order_no' => 'TEST' . date('YmdHis') . rand(1000, 9999),
                'user_id' => $testUser->id,
                'vip_plan_id' => $vipPlans[0]->id,
                'plan_name' => $vipPlans[0]->name,
                'original_price' => $vipPlans[0]->price,
                'discount_amount' => 0,
                'final_price' => $vipPlans[0]->final_price,
                'payment_method' => 'alipay',
                'payment_trade_no' => '2024' . rand(100000000000, 999999999999),
                'status' => 'paid',
                'paid_at' => now()->subDays(5),
                'created_at' => now()->subDays(5),
            ]);

            // 待支付订单
            Order::create([
                'order_no' => 'TEST' . date('YmdHis') . rand(1000, 9999),
                'user_id' => $testUser->id,
                'vip_plan_id' => $vipPlans->count() > 1 ? $vipPlans[1]->id : $vipPlans[0]->id,
                'plan_name' => $vipPlans->count() > 1 ? $vipPlans[1]->name : $vipPlans[0]->name,
                'original_price' => $vipPlans->count() > 1 ? $vipPlans[1]->price : $vipPlans[0]->price,
                'discount_amount' => 0,
                'final_price' => $vipPlans->count() > 1 ? $vipPlans[1]->final_price : $vipPlans[0]->final_price,
                'payment_method' => 'alipay',
                'status' => 'pending',
                'expired_at' => now()->addHours(2),
            ]);
        }

        echo "测试数据创建成功！\n";
        echo "测试用户账号：\n";
        echo "- 13800138001 (普通用户)\n";
        echo "- 13800138002 (普通用户)\n";
        echo "- 13800138003 (VIP用户)\n";
        echo "密码统一为: 123456\n";
    }
}
