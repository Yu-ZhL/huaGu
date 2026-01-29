<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CouponSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $expired = $now->copy()->subMonth();
        $future = $now->copy()->addMonth();

        $coupons = [
            [
                'code' => 'NEWUSER2026',
                'name' => '新用户专享优惠',
                'type' => 'fixed',
                'discount_value' => 10.00,
                'min_amount' => 100.00,
                'total_count' => 1000,
                'used_count' => 0,
                'start_at' => $now,
                'end_at' => $future,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'VIP88',
                'name' => 'VIP 88折优惠',
                'type' => 'percent',
                'discount_value' => 12.00, // 代表 12% off，即88折
                'min_amount' => 200.00,
                'total_count' => 500,
                'used_count' => 10,
                'start_at' => $now,
                'end_at' => $future,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'EXPIRED2025',
                'name' => '已过期优惠码',
                'type' => 'fixed',
                'discount_value' => 50.00,
                'min_amount' => 0.00,
                'total_count' => 100,
                'used_count' => 100,
                'start_at' => $expired,
                'end_at' => $expired, // 已过期
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'TEST10',
                'name' => '测试立减10元',
                'type' => 'fixed',
                'discount_value' => 10.00,
                'min_amount' => 0.00,
                'total_count' => 9999,
                'used_count' => 0,
                'start_at' => $now,
                'end_at' => $future,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        ];

        // 使用 insertOrIgnore 防止重复插入报错
        DB::table('coupons')->insertOrIgnore($coupons);
    }
}
