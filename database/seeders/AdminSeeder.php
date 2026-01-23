<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 只有当没有该邮箱的管理员时才创建，防止重复
        \App\Models\Admin::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Admin',
                'password' => '123456', // 会被模型里的 cast 自动加密
                'avatar' => null,
            ]
        );
    }
}
