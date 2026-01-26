<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    // 批量赋值白名单
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'phone_area_code',
        'invitation_code',
        'plaintext_password',
        'vip_level_id',
        'vip_expired_at',
        'ai_points',
        'last_login_ip',
        'last_login_location',
        'last_login_at',
        'register_ip',
        'register_location',
        'status',
        'is_sub_account',
        'parent_id',
        'remark',
    ];

    // JSON序列化时隐藏这些字段，安全第一
    protected $hidden = [
        'password',
        'remember_token',
        'plaintext_password', // 明文
    ];

    // 属性类型转换
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'vip_expired_at' => 'datetime',
            'last_login_at' => 'datetime',
            'status' => 'boolean',
            'is_sub_account' => 'boolean',
            'ai_points' => 'integer',
        ];
    }

    public function vipPlan()
    {
        return $this->belongsTo(VipPlan::class, 'vip_level_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function aiPointRecords()
    {
        return $this->hasMany(UserAiPoint::class);
    }

    public function isVip(): bool
    {
        return $this->vip_level_id > 0 &&
            $this->vip_expired_at &&
            $this->vip_expired_at->isFuture();
    }

    public function addAiPoints(int $points, string $type, ?string $description = null, ?int $sourceId = null, ?\DateTime $expiredAt = null)
    {
        return UserAiPoint::addPoints($this->id, $points, $type, $description, $sourceId, $expiredAt);
    }

    public function deductAiPoints(int $points, string $type, ?string $description = null, ?int $sourceId = null)
    {
        return UserAiPoint::deductPoints($this->id, $points, $type, $description, $sourceId);
    }


    public function getAvailableAiPoints(): int
    {
        return $this->ai_points;
    }
}
