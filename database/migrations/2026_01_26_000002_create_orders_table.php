<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->comment('订单表');
            $table->id();
            $table->string('order_no', 32)->unique()->comment('订单号');
            $table->unsignedBigInteger('user_id')->comment('用户ID');
            $table->unsignedBigInteger('vip_plan_id')->comment('VIP套餐ID');
            $table->unsignedBigInteger('coupon_id')->nullable()->comment('优惠码ID');
            $table->string('plan_name')->comment('套餐名称快照');
            $table->decimal('original_price', 10, 2)->comment('原价快照');
            $table->decimal('discount_amount', 10, 2)->default(0)->comment('优惠金额');
            $table->decimal('final_price', 10, 2)->comment('实付金额');
            $table->string('payment_method', 20)->default('alipay')->comment('支付方式');
            $table->string('payment_trade_no')->nullable()->comment('支付平台交易号');
            $table->string('status', 20)->default('pending')->comment('订单状态');
            $table->timestamp('paid_at')->nullable()->comment('支付时间');
            $table->timestamp('expired_at')->nullable()->comment('订单过期时间');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('vip_plan_id')->references('id')->on('vip_plans');
            $table->index('order_no');
            $table->index('user_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
