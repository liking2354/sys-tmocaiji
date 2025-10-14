<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cloud_platforms', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('平台名称');
            $table->enum('platform_type', ['huawei', 'alibaba', 'tencent'])->comment('平台类型');
            $table->string('access_key_id')->comment('访问密钥ID');
            $table->string('access_key_secret')->comment('访问密钥Secret');
            $table->string('region')->nullable()->comment('默认区域');
            $table->unsignedBigInteger('user_id')->comment('用户ID');
            $table->enum('status', ['active', 'inactive'])->default('active')->comment('状态');
            $table->json('config')->nullable()->comment('额外配置信息');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'platform_type']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cloud_platforms');
    }
};