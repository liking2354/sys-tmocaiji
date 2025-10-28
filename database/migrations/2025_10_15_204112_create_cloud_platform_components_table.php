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
        Schema::create('cloud_platform_components', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('platform_id')->comment('云平台ID');
            $table->unsignedBigInteger('component_dict_id')->comment('组件字典ID');
            $table->boolean('is_enabled')->default(true)->comment('是否启用');
            $table->integer('sync_priority')->default(0)->comment('同步优先级');
            $table->json('config')->nullable()->comment('组件配置');
            $table->timestamp('last_sync_at')->nullable()->comment('最后同步时间');
            $table->timestamps();

            $table->foreign('platform_id')->references('id')->on('cloud_platforms')->onDelete('cascade');
            $table->foreign('component_dict_id')->references('id')->on('dict_items')->onDelete('cascade');
            $table->index(['platform_id', 'is_enabled']);
            $table->index('sync_priority');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cloud_platform_components');
    }
};
