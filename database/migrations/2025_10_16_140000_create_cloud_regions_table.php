<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cloud_regions', function (Blueprint $table) {
            $table->id();
            $table->string('platform_type', 50)->comment('平台类型：huawei, alibaba, tencent');
            $table->string('region_code', 100)->comment('区域代码');
            $table->string('region_name')->comment('区域名称');
            $table->string('endpoint')->nullable()->comment('区域端点');
            $table->boolean('is_active')->default(true)->comment('是否启用');
            $table->text('description')->nullable()->comment('区域描述');
            $table->json('metadata')->nullable()->comment('区域元数据');
            $table->timestamps();
            
            // 索引
            $table->index(['platform_type', 'is_active']);
            $table->unique(['platform_type', 'region_code'], 'unique_platform_region');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cloud_regions');
    }
};