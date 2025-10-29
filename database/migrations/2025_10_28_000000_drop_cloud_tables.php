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
        // 删除所有云资源相关的表
        Schema::dropIfExists('cloud_compute_resources');
        Schema::dropIfExists('cloud_network_resources');
        Schema::dropIfExists('cloud_database_resources');
        Schema::dropIfExists('cloud_resources');
        Schema::dropIfExists('cloud_region_resource_support');
        Schema::dropIfExists('cloud_regions');
        Schema::dropIfExists('cloud_platform_components');
        Schema::dropIfExists('cloud_platforms');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // 回滚时不做任何操作
    }
};
