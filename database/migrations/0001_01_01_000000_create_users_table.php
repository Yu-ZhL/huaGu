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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable(); // 姓名/昵称
            $table->string('email')->nullable()->unique(); // 邮箱
            $table->string('phone')->nullable()->unique(); // 手机号
            $table->string('phone_area_code')->default('86'); // 手机区号
            $table->string('invitation_code')->nullable()->index(); // 邀请码
            $table->string('password'); // 密码
            $table->string('plaintext_password')->nullable(); // 明文密码 (Critical: Security Risk, User Requirement)

            // VIP info
            $table->unsignedBigInteger('vip_level_id')->default(0); // VIP等级id
            $table->timestamp('vip_expired_at')->nullable(); // VIP过期时间

            // Login & Access info
            $table->ipAddress('last_login_ip')->nullable(); // 最近登录IP
            $table->string('last_login_location')->nullable(); // 最近登录地点
            $table->timestamp('last_login_at')->nullable(); // 最近登录时间

            // Register info
            $table->ipAddress('register_ip')->nullable(); // 账号创建IP
            $table->string('register_location')->nullable(); // 账号创建地址

            // Status & Hierarchy
            $table->tinyInteger('status')->default(1); // 账号状态 1:正常, 0:禁用
            $table->boolean('is_sub_account')->default(false); // 是否是子账号
            $table->unsignedBigInteger('parent_id')->nullable()->index(); // 父账号id

            $table->text('remark')->nullable(); // 备注

            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
