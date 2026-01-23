<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')->default(0)->index(); // 父级菜单ID
            $table->string('title'); // 菜单名称
            $table->string('icon')->nullable(); // 图标 class 或 path
            $table->string('route')->nullable(); // 路由名称或路径
            $table->string('url')->nullable(); // 直接URL链接
            $table->integer('sort')->default(0); // 排序
            $table->boolean('is_hidden')->default(false); // 是否隐藏
            $table->boolean('is_active')->default(true); // 是否启用

            // 权限控制字段 (可选)
            $table->string('permission')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menus');
    }
};
