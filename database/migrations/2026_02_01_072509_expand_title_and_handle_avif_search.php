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
            $table->text('title')->nullable()->comment('商品标题，使用文本类型防止超长截断')->change();
        });

        Schema::table('product_1688_sources', function (Blueprint $table) {
            $table->text('title')->nullable()->comment('1688商品标题，使用文本类型防止超长截断')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('temu_collected_products', function (Blueprint $table) {
            $table->string('title', 255)->nullable()->change();
        });

        Schema::table('product_1688_sources', function (Blueprint $table) {
            $table->string('title', 255)->nullable()->change();
        });
    }
};
