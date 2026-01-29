<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('temu_collected_products', function (Blueprint $table) {
            $table->comment('Temu商品采集表');
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('用户ID');
            $table->string('product_id', 100)->comment('Temu商品ID');
            $table->string('site_url', 500)->nullable()->comment('采集站点URL');
            $table->string('platform', 50)->default('temu')->comment('平台标识');

            $table->string('title')->nullable()->comment('商品标题');
            $table->decimal('sale_price', 10, 2)->nullable()->comment('销售价格');
            $table->decimal('weight', 10, 3)->nullable()->comment('商品重量(kg)');
            $table->string('brand', 100)->nullable()->comment('品牌');
            $table->string('cover_image', 500)->nullable()->comment('封面图片');

            $table->json('product_data')->nullable()->comment('完整商品数据(JSON)');

            $table->timestamp('collected_at')->nullable()->comment('采集时间');
            $table->timestamps();

            $table->index('user_id');
            $table->index('product_id');
            $table->index(['user_id', 'product_id']);

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('temu_collected_products');
    }
};
