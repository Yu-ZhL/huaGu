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
        Schema::create('sms_codes', function (Blueprint $table) {
            $table->comment('短信验证码表');
            $table->id();
            $table->string('phone_area_code', 10)->default('86')->comment('区号');
            $table->string('phone', 20)->comment('手机号');
            $table->string('code', 10)->comment('验证码');
            $table->timestamp('expires_at')->comment('过期时间');
            $table->timestamp('used_at')->nullable()->comment('使用时间');
            $table->ipAddress('ip')->nullable()->comment('请求IP');
            $table->timestamps();

            $table->index(['phone_area_code', 'phone', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_codes');
    }
};
