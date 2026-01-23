<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    // 菜单表字段
    protected $fillable = [
        'parent_id', // 父级ID，顶级菜单是0
        'title', // 菜单显示名称
        'icon', // 图标
        'route', // 对应的路由名
        'url', // 如果是外链或者写死的路径
        'sort', // 排序，数字越小越靠前
        'is_hidden', // 是否在侧边栏隐藏
        'is_active', // 是否启用
        'permission', // 权限标识，预留字段
    ];

    protected $casts = [
        'is_hidden' => 'boolean',
        'is_active' => 'boolean',
        'sort' => 'integer',
    ];

    // 关联父级菜单
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }

    // 关联子菜单
    public function children(): HasMany
    {
        return $this->hasMany(Menu::class, 'parent_id');
    }
}
