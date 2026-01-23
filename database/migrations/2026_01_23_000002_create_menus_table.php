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
            $table->comment('后台菜单表'); // 表注释
            $table->id();
            $table->unsignedBigInteger('parent_id')->default(0)->index()->comment('父级菜单ID，0表示顶级菜单');
            $table->string('title')->comment('菜单显示名称');
            $table->string('icon')->nullable()->comment('图标类名，支持 Heroicons 等');
            $table->string('route')->nullable()->comment('关联的 Laravel 路由名称');
            $table->string('url')->nullable()->comment('外部链接 URL');
            $table->integer('sort')->default(0)->comment('排序权重，数字越小越靠前');
            $table->boolean('is_hidden')->default(false)->comment('是否隐藏，1-隐藏，0-显示');
            $table->boolean('is_active')->default(true)->comment('是否启用，1-启用，0-禁用');

            // 权限控制字段 (可选)
            $table->string('permission')->nullable()->comment('权限标识，用于控制访问');

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
