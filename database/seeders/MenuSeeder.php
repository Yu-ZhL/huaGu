<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. 仪表盘
        \App\Models\Menu::create([
            'parent_id' => 0,
            'title' => '仪表盘',
            'icon' => 'heroicon-o-home',
            'route' => 'filament.admin.pages.dashboard',
            'sort' => 0,
            'is_hidden' => false,
            'is_active' => true,
        ]);

        // 2. 系统管理 (父级)
        $system = \App\Models\Menu::create([
            'parent_id' => 0,
            'title' => '系统管理',
            'icon' => 'heroicon-o-cog-6-tooth',
            'route' => null, // 父级菜单通常没有直接路由
            'sort' => 99,
            'is_hidden' => false,
            'is_active' => true,
        ]);

        // 2.1 用户管理
        \App\Models\Menu::create([
            'parent_id' => $system->id,
            'title' => '用户管理',
            'icon' => 'heroicon-o-users',
            'route' => 'filament.admin.resources.users.index',
            'sort' => 1,
            'is_hidden' => false,
            'is_active' => true,
        ]);

        // 2.2 管理员管理
        \App\Models\Menu::create([
            'parent_id' => $system->id,
            'title' => '管理员管理',
            'icon' => 'heroicon-o-shield-check',
            'route' => 'filament.admin.resources.admins.index',
            'sort' => 2,
            'is_hidden' => false,
            'is_active' => true,
        ]);

        // 2.3 菜单管理
        \App\Models\Menu::create([
            'parent_id' => $system->id,
            'title' => '菜单管理',
            'icon' => 'heroicon-o-bars-3',
            'route' => 'filament.admin.resources.menus.index',
            'sort' => 3,
            'is_hidden' => false,
            'is_active' => true,
        ]);
    }
}
