<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsCode extends Model
{
    protected $fillable = [
        'phone_area_code',
        'phone',
        'code',
        'expires_at',
        'used_at',
        'ip',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isUsed(): bool
    {
        return !is_null($this->used_at);
    }

    public function markAsUsed(): void
    {
        $this->update(['used_at' => now()]);
    }
}
