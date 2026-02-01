<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class SiteSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            [
                'key' => 'new_user_gift_points',
                'value' => '500',
                'type' => 'integer',
                'label' => '新用户赠送AI点数',
                'description' => '新用户注册时自动赠送的AI点数数量',
            ],
            [
                'key' => 'gift_points_expire_days',
                'value' => '30',
                'type' => 'integer',
                'label' => '赠送点数有效期（天）',
                'description' => '新用户注册赠送的AI点数的有效期，单位为天',
            ],
            [
                'key' => 'ai_points_1688_search',
                'value' => '2',
                'type' => 'integer',
                'label' => '1688同款查询消耗点数',
                'description' => '每次调用1688图搜API消耗的AI点数',
            ],
            [
                'key' => 'ai_points_profit_calc',
                'value' => '1',
                'type' => 'integer',
                'label' => 'AI利润计算消耗点数',
                'description' => '每次AI计算利润消耗的点数(暂未启用)',
            ],
        ];

        foreach ($settings as $setting) {
            SiteSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
