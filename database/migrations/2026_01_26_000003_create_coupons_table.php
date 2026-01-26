<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->comment('优惠码表');
            $table->id();
            $table->string('code', 50)->unique()->comment('优惠码');
            $table->string('name')->comment('优惠码名称');
            $table->string('type', 20)->comment('类型 fixed固定金额 percent百分比');
            $table->decimal('discount_value', 10, 2)->comment('折扣值');
            $table->decimal('min_amount', 10, 2)->default(0)->comment('最低消费金额');
            $table->integer('total_count')->default(0)->comment('总发行数量 0为不限');
            $table->integer('used_count')->default(0)->comment('已使用数量');
            $table->timestamp('start_at')->nullable()->comment('生效时间');
            $table->timestamp('end_at')->nullable()->comment('失效时间');
            $table->tinyInteger('status')->default(1)->comment('状态 1启用 0禁用');
            $table->timestamps();

            $table->index('code');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
