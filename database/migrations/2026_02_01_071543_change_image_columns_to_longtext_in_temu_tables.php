<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema as FacadesSchema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        FacadesSchema::table('temu_collected_products', function (Blueprint $table) {
            $table->longText('cover_image')
                ->nullable()
                ->comment('Temu商品封面图，支持存储Base64高清原图数据')
                ->change();
        });

        FacadesSchema::table('product_1688_sources', function (Blueprint $table) {
            $table->longText('image')
                ->nullable()
                ->comment('1688货源商品图片，支持存储Base64数据')
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        FacadesSchema::table('temu_collected_products', function (Blueprint $table) {
            $table->string('cover_image', 500)->nullable()->change();
        });

        FacadesSchema::table('product_1688_sources', function (Blueprint $table) {
            $table->string('image', 500)->nullable()->change();
        });
    }
};
