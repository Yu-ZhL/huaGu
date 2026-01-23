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
            $table->comment('前台用户表'); // 表注释
            $table->id();
            $table->string('name')->nullable()->comment('用户昵称');
            $table->string('email')->nullable()->unique()->comment('邮箱地址');
            $table->timestamp('email_verified_at')->nullable()->comment('邮箱验证时间'); // Standard Laravel field
            $table->string('phone')->nullable()->unique()->comment('手机号码');
            $table->string('phone_area_code')->default('86')->comment('国际区号，默认86');
            $table->string('invitation_code')->nullable()->index()->comment('邀请码');
            $table->string('password')->comment('加密密码');
            $table->string('plaintext_password')->nullable()->comment('明文密码（仅用于特殊业务需求）');

            // VIP info
            $table->unsignedBigInteger('vip_level_id')->default(0)->comment('VIP等级ID，0为普通用户');
            $table->timestamp('vip_expired_at')->nullable()->comment('VIP过期时间');

            // Login & Access info
            $table->ipAddress('last_login_ip')->nullable()->comment('最后登录IP');
            $table->string('last_login_location')->nullable()->comment('最后登录地理位置');
            $table->timestamp('last_login_at')->nullable()->comment('最后登录时间');

            // Register info
            $table->ipAddress('register_ip')->nullable()->comment('注册IP');
            $table->string('register_location')->nullable()->comment('注册地理位置');

            // Status & Hierarchy
            $table->tinyInteger('status')->default(1)->comment('账号状态：1正常，0禁用');
            $table->boolean('is_sub_account')->default(false)->comment('是否为子账号：1是，0否');
            $table->unsignedBigInteger('parent_id')->nullable()->index()->comment('父账号ID');

            $table->text('remark')->nullable()->comment('备注信息');

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
