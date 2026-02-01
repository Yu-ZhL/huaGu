<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'key',
        'value',
        'type',
        'label',
        'description',
    ];

    public static function get(string $key, $default = null)
    {
        $setting = self::find($key);

        if (!$setting) {
            return $default;
        }

        return match ($setting->type) {
            'integer' => (int) $setting->value,
            'boolean' => (bool) $setting->value,
            'json' => json_decode($setting->value, true),
            default => $setting->value,
        };
    }

    public static function set(string $key, $value, ?string $type = null): void
    {
        $setting = self::find($key);

        if (!$setting) {
            if (!$type) {
                $type = is_int($value) ? 'integer' : (is_bool($value) ? 'boolean' : 'text');
            }

            self::create([
                'key' => $key,
                'value' => is_array($value) ? json_encode($value) : $value,
                'type' => $type,
                'label' => $key,
            ]);
        } else {
            $setting->update([
                'value' => is_array($value) ? json_encode($value) : $value,
            ]);
        }
    }

    public static function getNewUserGiftPoints(): int
    {
        return self::get('new_user_gift_points', 500);
    }

    public static function getGiftPointsExpireDays(): int
    {
        return self::get('gift_points_expire_days', 30);
    }

    public static function getMaxRegisterPerIp(): int
    {
        return self::get('max_register_per_ip', 3);
    }

    public static function getAiPoints1688Search(): int
    {
        return self::get('ai_points_1688_search', 2);
    }

    public static function getAiPointsProfitCalc(): int
    {
        return self::get('ai_points_profit_calc', 1);
    }

    public static function getAiPointsTaobaoSearch(): int
    {
        return self::get('ai_points_taobao_search', 2);
    }
}
