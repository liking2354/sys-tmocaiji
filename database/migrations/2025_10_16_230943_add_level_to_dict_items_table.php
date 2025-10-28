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
        Schema::table('dict_items', function (Blueprint $table) {
            // 添加level字段，用于表示层级（1-3级）
            $table->tinyInteger('level')->default(1)->comment('层级：1-一级，2-二级，3-三级')->after('parent_id');
            
            // 添加platform_type字段，用于平台类型关联
            $table->string('platform_type', 50)->nullable()->comment('平台类型：alibaba, tencent, huawei')->after('level');
            
            // 添加metadata字段，用于存储额外的元数据
            $table->json('metadata')->nullable()->comment('元数据信息')->after('extra_data');
            
            // 添加索引以提高查询性能
            $table->index(['category_id', 'level', 'status'], 'idx_category_level_status');
            $table->index(['platform_type', 'level'], 'idx_platform_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dict_items', function (Blueprint $table) {
            // 删除索引
            $table->dropIndex('idx_category_level_status');
            $table->dropIndex('idx_platform_level');
            
            // 删除字段
            $table->dropColumn(['level', 'platform_type', 'metadata']);
        });
    }
};