<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    const TYPE_FIXED = 'fixed';
    const TYPE_PERCENT = 'percent';

    protected $fillable = [
        'code',
        'name',
        'type',
        'discount_value',
        'min_amount',
        'total_count',
        'used_count',
        'start_at',
        'end_at',
        'status',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'min_amount' => 'decimal:2',
        'total_count' => 'integer',
        'used_count' => 'integer',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'status' => 'boolean',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function isValid(float $amount = 0): array
    {
        if (!$this->status) {
            return ['valid' => false, 'message' => '优惠码已禁用'];
        }

        if ($this->start_at && $this->start_at->isFuture()) {
            return ['valid' => false, 'message' => '优惠码尚未生效'];
        }

        if ($this->end_at && $this->end_at->isPast()) {
            return ['valid' => false, 'message' => '优惠码已过期'];
        }

        if ($this->total_count > 0 && $this->used_count >= $this->total_count) {
            return ['valid' => false, 'message' => '优惠码已用完'];
        }

        if ($amount > 0 && $amount < $this->min_amount) {
            return ['valid' => false, 'message' => "需满{$this->min_amount}元才可使用"];
        }

        return ['valid' => true, 'message' => '优惠码可用'];
    }

    public function calculateDiscount(float $amount): float
    {
        if ($this->type === self::TYPE_FIXED) {
            return min($this->discount_value, $amount);
        }

        return round($amount * $this->discount_value / 100, 2);
    }

    public function incrementUsedCount(): void
    {
        $this->increment('used_count');
    }
}
