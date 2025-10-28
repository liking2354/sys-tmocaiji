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
        Schema::table('cloud_resources', function (Blueprint $table) {
            // 添加新的字段以支持重构需求
            $table->unsignedBigInteger('resource_type_dict_id')->nullable()->after('resource_type')->comment('资源类型字典ID');
            $table->unsignedBigInteger('component_dict_id')->nullable()->after('resource_type_dict_id')->comment('组件类型字典ID');
            $table->json('standard_data')->nullable()->after('raw_data')->comment('标准化数据');
            $table->timestamp('sync_at')->nullable()->after('last_sync_at')->comment('同步时间');
            
            // 添加外键约束（如果字典表存在）
            $table->foreign('resource_type_dict_id')->references('id')->on('dict_items')->onDelete('set null');
            $table->foreign('component_dict_id')->references('id')->on('dict_items')->onDelete('set null');
            
            // 添加新的索引
            $table->index('resource_type_dict_id');
            $table->index('component_dict_id');
            $table->index('sync_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cloud_resources', function (Blueprint $table) {
            // 删除外键约束
            $table->dropForeign(['resource_type_dict_id']);
            $table->dropForeign(['component_dict_id']);
            
            // 删除索引
            $table->dropIndex(['resource_type_dict_id']);
            $table->dropIndex(['component_dict_id']);
            $table->dropIndex(['sync_at']);
            
            // 删除字段
            $table->dropColumn(['resource_type_dict_id', 'component_dict_id', 'standard_data', 'sync_at']);
        });
    }
};