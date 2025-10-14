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
        Schema::create('cloud_resources', function (Blueprint $table) {
            $table->id();
            $table->string('resource_id')->comment('云平台资源ID');
            $table->enum('resource_type', ['ecs', 'clb', 'cdb', 'redis', 'domain'])->comment('资源类型');
            $table->string('name')->comment('资源名称');
            $table->string('status')->comment('资源状态');
            $table->string('region')->comment('所属区域');
            $table->unsignedBigInteger('platform_id')->comment('云平台ID');
            $table->unsignedBigInteger('user_id')->comment('用户ID');
            $table->json('raw_data')->comment('原始数据');
            $table->json('metadata')->nullable()->comment('元数据');
            $table->timestamp('last_sync_at')->nullable()->comment('最后同步时间');
            $table->timestamps();

            $table->foreign('platform_id')->references('id')->on('cloud_platforms')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['platform_id', 'resource_id']);
            $table->index(['user_id', 'resource_type']);
            $table->index(['platform_id', 'resource_type']);
            $table->index('status');
            $table->index('region');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cloud_resources');
    }
};