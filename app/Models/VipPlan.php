<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VipPlan extends Model
{
    protected $fillable = [
        'name',
        'price',
        'final_price',
        'ai_points',
        'duration_days',
        'features',
        'is_recommended',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'final_price' => 'decimal:2',
        'ai_points' => 'integer',
        'duration_days' => 'integer',
        'features' => 'array',
        'is_recommended' => 'boolean',
        'sort_order' => 'integer',
        'status' => 'boolean',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
