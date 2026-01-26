<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->comment('网站配置表');
            $table->string('key', 100)->primary()->comment('配置键');
            $table->text('value')->nullable()->comment('配置值');
            $table->string('type', 20)->default('text')->comment('类型');
            $table->string('label')->comment('显示名称');
            $table->text('description')->nullable()->comment('描述');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
