<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // 清空现有数据
        DB::table('dict_items')->delete();
        DB::table('dict_categories')->delete();
        
        // 重置自增ID
        DB::statement('ALTER TABLE dict_categories AUTO_INCREMENT = 1');
        DB::statement('ALTER TABLE dict_items AUTO_INCREMENT = 1');
        
        // 添加层级字段到字典项表
        Schema::table('dict_items', function (Blueprint $table) {
            $table->integer('level')->default(1)->comment('层级：1-一级，2-二级，3-三级');
            $table->string('platform_type')->nullable()->comment('关联的平台类型');
            $table->text('metadata')->nullable()->comment('扩展元数据JSON');
        });
    }

    public function down()
    {
        Schema::table('dict_items', function (Blueprint $table) {
            $table->dropColumn(['level', 'platform_type', 'metadata']);
        });
    }
};