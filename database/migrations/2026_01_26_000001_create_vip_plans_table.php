<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vip_plans', function (Blueprint $table) {
            $table->comment('VIP套餐表');
            $table->id();
            $table->string('name')->comment('套餐名称');
            $table->text('description')->nullable()->comment('套餐描述');
            $table->decimal('price', 10, 2)->comment('原价');
            $table->decimal('final_price', 10, 2)->comment('实际售价');
            $table->integer('ai_points')->default(0)->comment('AI点数额度');
            $table->integer('duration_days')->default(30)->comment('有效期天数');
            $table->json('features')->nullable()->comment('功能列表');
            $table->boolean('is_recommended')->default(false)->comment('是否推荐');
            $table->integer('sort_order')->default(0)->comment('排序');
            $table->tinyInteger('status')->default(1)->comment('状态 1启用 0禁用');
            $table->timestamps();

            $table->index('status');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vip_plans');
    }
};
