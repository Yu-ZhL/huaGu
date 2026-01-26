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
                'price' => 68.00,
                'final_price' => 68.00,
                'ai_points' => 6000,
                'duration_days' => 30,
                'features' => [],
                'is_recommended' => false,
                'sort_order' => 1,
                'status' => 1,
            ],
            [
                'name' => '月度会员 128元',
                'price' => 128.00,
                'final_price' => 128.00,
                'ai_points' => 15000,
                'duration_days' => 30,
                'features' => [],
                'is_recommended' => true,
                'sort_order' => 2,
                'status' => 1,
            ],
            [
                'name' => '月度会员 188元',
                'price' => 188.00,
                'final_price' => 188.00,
                'ai_points' => 30000,
                'duration_days' => 30,
                'features' => [],
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
