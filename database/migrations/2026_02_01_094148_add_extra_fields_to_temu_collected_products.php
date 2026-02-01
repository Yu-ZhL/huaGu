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
        Schema::table('temu_collected_products', function (Blueprint $table) {
            $table->text('remark')->nullable()->after('product_data')->comment('备注');
            $table->integer('sales')->default(0)->after('remark')->comment('总销量');
            $table->integer('reviews')->default(0)->after('sales')->comment('评价数');
            $table->decimal('rating', 3, 2)->default(0)->after('reviews')->comment('评分');
            $table->decimal('freight', 10, 2)->default(0)->after('rating')->comment('预估运费(¥)');
            $table->decimal('profit', 10, 2)->default(0)->after('freight')->comment('预估利润(¥)');
            $table->decimal('source_price_1688', 10, 2)->default(0)->after('profit')->comment('1688货源价格(¥)');
            $table->boolean('is_brand')->default(false)->after('source_price_1688')->comment('是否品牌');
            $table->string('shop_name', 200)->nullable()->after('is_brand')->comment('店铺名称');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('temu_collected_products', function (Blueprint $table) {
            $table->dropColumn([
                'remark',
                'sales',
                'reviews',
                'rating',
                'freight',
                'profit',
                'source_price_1688',
                'is_brand',
                'shop_name'
            ]);
        });
    }
};
