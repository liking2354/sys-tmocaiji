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
        Schema::create('cloud_regions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('platform_id')->comment('云平台ID');
            $table->string('region_code')->comment('区域代码');
            $table->string('region_name')->comment('区域名称');
            $table->string('endpoint')->nullable()->comment('区域端点');
            $table->boolean('is_active')->default(true)->comment('是否启用');
            $table->text('description')->nullable()->comment('描述');
            $table->json('metadata')->nullable()->comment('区域元数据');
            $table->timestamps();

            $table->foreign('platform_id')->references('id')->on('cloud_platforms')->onDelete('cascade');
            $table->unique(['platform_id', 'region_code']);
            $table->index('platform_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cloud_regions');
    }
};