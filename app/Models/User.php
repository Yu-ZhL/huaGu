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
        'phone', // 手机号
        'phone_area_code', // 区号，默认86
        'invitation_code', // 邀请码，推广用的
        'plaintext_password', // 明文密码 预留
        'vip_level_id', // VIP等级ID
        'vip_expired_at', // VIP过期时间
        'last_login_ip',
        'last_login_location',
        'last_login_at', // 最近一次登录时间
        'register_ip',
        'register_location',
        'status', // 账号状态，1是正常，0是禁用
        'is_sub_account', // 是不是子账号
        'parent_id', // 父账号ID
        'remark', // 备注信息
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
            'status' => 'boolean', // 数据库里是tinyint，这里转成布尔值方便判断
            'is_sub_account' => 'boolean',
        ];
    }
}
