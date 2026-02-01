<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemuCollectedProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'site_url',
        'platform',
        'title',
        'sale_price',
        'weight',
        'brand',
        'cover_image',
        'product_data',
        'collected_at',
        'remark',
        'sales',
        'reviews',
        'rating',
        'freight',
        'profit',
        'source_price_1688',
        'is_brand',
    ];

    protected $casts = [
        'product_data' => 'array',
        'sale_price' => 'decimal:2',
        'weight' => 'decimal:3',
        'collected_at' => 'datetime',
        'sales' => 'integer',
        'reviews' => 'integer',
        'rating' => 'decimal:2',
        'freight' => 'decimal:2',
        'profit' => 'decimal:2',
        'source_price_1688' => 'decimal:2',
        'is_brand' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sources1688()
    {
        return $this->hasMany(Product1688Source::class, 'temu_product_id');
    }

    public function primarySource()
    {
        return $this->hasOne(Product1688Source::class, 'temu_product_id')
            ->where('is_primary', true);
    }
}
