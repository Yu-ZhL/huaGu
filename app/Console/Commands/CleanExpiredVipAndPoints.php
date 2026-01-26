<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserAiPoint;
use Illuminate\Console\Command;

class CleanExpiredVipAndPoints extends Command
{
    protected $signature = 'vip:clean-expired';

    protected $description = '清理过期的VIP和过期的AI点数';

    public function handle()
    {
        $this->info('开始清理过期VIP和AI点数...');

        // 清理过期VIP
        $expiredVips = User::where('vip_level_id', '>', 0)
            ->where('vip_expired_at', '<', now())
            ->get();

        foreach ($expiredVips as $user) {
            $user->update([
                'vip_level_id' => 0,
                'vip_expired_at' => null,
            ]);
            $this->info("用户 {$user->id} 的VIP已过期并清理");
        }

        // 清理过期AI点数
        $cleanedCount = UserAiPoint::cleanExpiredPoints();

        $this->info("共清理 {$expiredVips->count()} 个过期VIP");
        $this->info("共清理 {$cleanedCount} 条过期AI点数记录");
        $this->info('清理完成！');

        return 0;
    }
}
