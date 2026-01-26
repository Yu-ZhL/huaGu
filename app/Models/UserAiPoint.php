<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAiPoint extends Model
{
    // 类型常量
    const TYPE_PURCHASE = 'vip_purchase';       // 购买获得
    const TYPE_GIFT = 'register_gift';          // 赠送获得
    const TYPE_CONSUME = 'consumption';          // 消费扣除
    const TYPE_ADMIN_ADJUST = 'admin_adjust';   // 管理员调整

    // 旧常量保留（向后兼容）
    const TYPE_REGISTER_GIFT = 'register_gift';
    const TYPE_VIP_PURCHASE = 'vip_purchase';
    const TYPE_CONSUMPTION = 'consumption';

    protected $fillable = [
        'user_id',
        'points',
        'balance_after',
        'type',
        'source_id',
        'description',
        'expired_at',
    ];

    protected $casts = [
        'points' => 'integer',
        'balance_after' => 'integer',
        'expired_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function addPoints(
        int $userId,
        int $points,
        string $type,
        ?string $description = null,
        ?int $sourceId = null,
        ?\DateTime $expiredAt = null
    ): self {
        $user = User::findOrFail($userId);

        $newBalance = $user->ai_points + $points;

        $user->update(['ai_points' => $newBalance]);

        return self::create([
            'user_id' => $userId,
            'points' => $points,
            'balance_after' => $newBalance,
            'type' => $type,
            'description' => $description,
            'source_id' => $sourceId,
            'expired_at' => $expiredAt,
        ]);
    }

    public static function deductPoints(
        int $userId,
        int $points,
        string $type,
        ?string $description = null,
        ?int $sourceId = null
    ): self {
        $user = User::findOrFail($userId);

        if ($user->ai_points < $points) {
            throw new \Exception('AI点数不足');
        }

        $newBalance = $user->ai_points - $points;

        $user->update(['ai_points' => $newBalance]);

        return self::create([
            'user_id' => $userId,
            'points' => -$points,
            'balance_after' => $newBalance,
            'type' => $type,
            'description' => $description,
            'source_id' => $sourceId,
        ]);
    }

    public static function cleanExpiredPoints(): int
    {
        $expiredRecords = self::where('expired_at', '<', now())
            ->whereNotNull('expired_at')
            ->get();

        $count = 0;
        foreach ($expiredRecords as $record) {
            if ($record->points > 0) {
                $user = $record->user;
                $user->decrement('ai_points', $record->points);
                $count++;
            }
        }

        return $count;
    }
}
