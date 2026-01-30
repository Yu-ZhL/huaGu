<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product1688Source extends Model
{
    use HasFactory;

    protected $table = 'product_1688_sources';

    protected $fillable = [
        'temu_product_id',
        'user_id',
        'title',
        'price',
        'image',
        'url',
        'product_data',
        'tags',
        'is_primary',
        'search_method',
    ];

    protected $casts = [
        'product_data' => 'array',
        'tags' => 'array',
        'price' => 'decimal:2',
        'is_primary' => 'boolean',
    ];

    public function temuProduct()
    {
        return $this->belongsTo(TemuCollectedProduct::class, 'temu_product_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }
}
