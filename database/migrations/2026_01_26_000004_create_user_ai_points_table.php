<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_ai_points', function (Blueprint $table) {
            $table->comment('用户AI点数记录表');
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('用户ID');
            $table->integer('points')->comment('点数变动量');
            $table->integer('balance_after')->comment('操作后余额');
            $table->string('type', 30)->comment('类型');
            $table->unsignedBigInteger('source_id')->nullable()->comment('来源ID');
            $table->string('description')->nullable()->comment('描述');
            $table->timestamp('expired_at')->nullable()->comment('过期时间');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('user_id');
            $table->index('type');
            $table->index('expired_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_ai_points');
    }
};
