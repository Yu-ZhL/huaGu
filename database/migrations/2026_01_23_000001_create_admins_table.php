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
        Schema::create('admins', function (Blueprint $table) {
            $table->comment('后台管理员表'); // 表注释
            $table->id();
            $table->string('name')->comment('管理员姓名');
            $table->string('email')->unique()->comment('登录邮箱，唯一标识');
            $table->string('password')->comment('加密后的登录密码');
            $table->string('avatar')->nullable()->comment('头像路径');
            $table->rememberToken()->comment('“记住我”功能的令牌');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
