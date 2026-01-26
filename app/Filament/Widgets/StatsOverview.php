<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        // 获取当前时间点
        $now = now();
        $yearStart = $now->copy()->startOfYear();
        $monthStart = $now->copy()->startOfMonth();
        $weekStart = $now->copy()->startOfWeek();

        // 用户统计
        $totalUsers = User::count();
        $yearUsers = User::where('created_at', '>=', $yearStart)->count();
        $monthUsers = User::where('created_at', '>=', $monthStart)->count();
        $weekUsers = User::where('created_at', '>=', $weekStart)->count();

        // VIP用户统计
        $totalVipUsers = User::where('vip_level_id', '>', 0)
            ->where(function ($query) {
                $query->whereNull('vip_expired_at')
                    ->orWhere('vip_expired_at', '>', now());
            })
            ->count();

        // 充值订单统计（已支付的订单）
        $totalOrders = Order::where('status', 'paid')->count();
        $totalRevenue = Order::where('status', 'paid')->sum('final_price');
        $yearOrders = Order::where('status', 'paid')
            ->where('paid_at', '>=', $yearStart)
            ->count();
        $yearRevenue = Order::where('status', 'paid')
            ->where('paid_at', '>=', $yearStart)
            ->sum('final_price');
        $monthOrders = Order::where('status', 'paid')
            ->where('paid_at', '>=', $monthStart)
            ->count();
        $monthRevenue = Order::where('status', 'paid')
            ->where('paid_at', '>=', $monthStart)
            ->sum('final_price');
        $weekOrders = Order::where('status', 'paid')
            ->where('paid_at', '>=', $weekStart)
            ->count();
        $weekRevenue = Order::where('status', 'paid')
            ->where('paid_at', '>=', $weekStart)
            ->sum('final_price');

        return [
            Stat::make('用户总数', $totalUsers)
                ->description("本年：{$yearUsers} | 本月：{$monthUsers} | 本周：{$weekUsers}")
                ->descriptionIcon('heroicon-m-users')
                ->color('success')
                ->chart($this->getUserTrend()),

            Stat::make('VIP用户', $totalVipUsers)
                ->description('当前有效VIP用户数')
                ->descriptionIcon('heroicon-m-star')
                ->color('warning'),

            Stat::make('充值订单', $totalOrders)
                ->description("本年：{$yearOrders} | 本月：{$monthOrders} | 本周：{$weekOrders}")
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('info')
                ->chart($this->getOrderTrend()),

            Stat::make('总收入', '¥' . number_format($totalRevenue, 2))
                ->description(
                    "本年：¥" . number_format($yearRevenue, 2) . " | " .
                    "本月：¥" . number_format($monthRevenue, 2) . " | " .
                    "本周：¥" . number_format($weekRevenue, 2)
                )
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success')
                ->chart($this->getRevenueTrend()),
        ];
    }

    /**
     * 获取用户增长趋势（最近7天）
     */
    private function getUserTrend(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();
            $count = User::whereDate('created_at', $date)->count();
            $data[] = $count;
        }
        return $data;
    }

    /**
     * 获取订单趋势（最近7天）
     */
    private function getOrderTrend(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();
            $count = Order::where('status', 'paid')
                ->whereDate('paid_at', $date)
                ->count();
            $data[] = $count;
        }
        return $data;
    }

    /**
     * 获取收入趋势（最近7天）
     */
    private function getRevenueTrend(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();
            $revenue = Order::where('status', 'paid')
                ->whereDate('paid_at', $date)
                ->sum('final_price');
            $data[] = (int) $revenue;
        }
        return $data;
    }
}
