<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Order extends Model
{
    const STATUS_PENDING = 'pending';
    const STATUS_PAID = 'paid';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'order_no',
        'user_id',
        'vip_plan_id',
        'coupon_id',
        'plan_name',
        'original_price',
        'discount_amount',
        'final_price',
        'payment_method',
        'payment_trade_no',
        'status',
        'paid_at',
        'expired_at',
    ];

    protected $casts = [
        'original_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'final_price' => 'decimal:2',
        'paid_at' => 'datetime',
        'expired_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->order_no)) {
                $order->order_no = self::generateOrderNo();
            }
            if (empty($order->expired_at)) {
                $order->expired_at = now()->addMinutes(30);
            }
        });
    }

    public static function generateOrderNo(): string
    {
        return date('YmdHis') . Str::random(8);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vipPlan(): BelongsTo
    {
        return $this->belongsTo(VipPlan::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED ||
            ($this->status === self::STATUS_PENDING && $this->expired_at && $this->expired_at->isPast());
    }

    public function markAsPaid(string $tradeNo): void
    {
        $this->update([
            'status' => self::STATUS_PAID,
            'payment_trade_no' => $tradeNo,
            'paid_at' => now(),
        ]);
    }

    public function cancel(): void
    {
        if ($this->isPending()) {
            $this->update(['status' => self::STATUS_CANCELLED]);
        }
    }
}
