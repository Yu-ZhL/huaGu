<?php

namespace Database\Seeders;

use App\Models\VipPlan;
use Illuminate\Database\Seeder;

class VipPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => '月度会员 68元',
                'description' => 'ai点数6000 可使用运费测算功能 约 3000 次使用额度',
                'price' => 68.00,
                'final_price' => 68.00,
                'ai_points' => 6000,
                'duration_days' => 30,
                'features' => [
                    '6000 AI点数',
                    '运费测算功能',
                    '约3000次使用',
                ],
                'is_recommended' => false,
                'sort_order' => 1,
                'status' => 1,
            ],
            [
                'name' => '最受欢迎 月度会员 128元',
                'description' => 'ai点数15000 可使用运费测算功能 约 7500 次使用额度',
                'price' => 128.00,
                'final_price' => 128.00,
                'ai_points' => 15000,
                'duration_days' => 30,
                'features' => [
                    '15000 AI点数',
                    '运费测算功能',
                    '约7500次使用',
                    '推荐选择',
                ],
                'is_recommended' => true,
                'sort_order' => 2,
                'status' => 1,
            ],
            [
                'name' => '月度会员 188元',
                'description' => 'ai点数30000 可使用运费测算功能 约 15000 次使用额度',
                'price' => 188.00,
                'final_price' => 188.00,
                'ai_points' => 30000,
                'duration_days' => 30,
                'features' => [
                    '30000 AI点数',
                    '运费测算功能',
                    '约15000次使用',
                    '超值套餐',
                ],
                'is_recommended' => false,
                'sort_order' => 3,
                'status' => 1,
            ],
        ];

        foreach ($plans as $plan) {
            VipPlan::updateOrCreate(
                ['name' => $plan['name']],
                $plan
            );
        }
    }
}
