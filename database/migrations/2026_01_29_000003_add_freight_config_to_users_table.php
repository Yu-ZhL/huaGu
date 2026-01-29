<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('freight_price_per_kg', 10, 2)->default(85.00)->comment('运费单价(元/kg)');
            $table->decimal('operation_fee', 10, 2)->default(17.00)->comment('操作费(元)');
            $table->timestamp('freight_config_updated_at')->nullable()->comment('运费配置最后修改时间');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['freight_price_per_kg', 'operation_fee', 'freight_config_updated_at']);
        });
    }
};
