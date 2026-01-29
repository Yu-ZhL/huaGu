<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_1688_sources', function (Blueprint $table) {
            $table->comment('1688同款货源表');
            $table->id();
            $table->unsignedBigInteger('temu_product_id')->comment('关联的Temu商品ID');
            $table->unsignedBigInteger('user_id')->comment('用户ID');

            $table->string('title')->nullable()->comment('1688商品标题');
            $table->decimal('price', 10, 2)->nullable()->comment('采购价格');
            $table->string('image', 500)->nullable()->comment('商品图片');
            $table->string('url', 500)->nullable()->comment('商品链接');

            $table->json('product_data')->nullable()->comment('完整商品数据(JSON)');
            $table->json('tags')->nullable()->comment('商品标签(如7天无理由等)');

            $table->boolean('is_primary')->default(false)->comment('是否为主选货源');
            $table->string('search_method', 20)->nullable()->comment('搜索方式(image/url)');

            $table->timestamps();

            $table->index('temu_product_id');
            $table->index('user_id');
            $table->index(['temu_product_id', 'is_primary']);

            $table->foreign('temu_product_id')
                ->references('id')->on('temu_collected_products')
                ->onDelete('cascade');
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_1688_sources');
    }
};
