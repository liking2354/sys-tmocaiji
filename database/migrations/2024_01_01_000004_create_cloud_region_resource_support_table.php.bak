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
        Schema::create('cloud_region_resource_support', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('region_id')->comment('区域ID');
            $table->enum('resource_type', ['ecs', 'clb', 'cdb', 'redis', 'domain'])->comment('资源类型');
            $table->boolean('is_supported')->default(true)->comment('是否支持');
            $table->json('limitations')->nullable()->comment('限制条件');
            $table->timestamps();

            $table->foreign('region_id')->references('id')->on('cloud_regions')->onDelete('cascade');
            $table->unique(['region_id', 'resource_type']);
            $table->index('resource_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cloud_region_resource_support');
    }
};