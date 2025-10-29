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
        // 删除字典项表（先删除有外键的表）
        Schema::dropIfExists('dict_items');
        
        // 删除字典分类表
        Schema::dropIfExists('dict_categories');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 如果需要回滚，可以在这里重新创建表
        // 但通常不建议在生产环境中回滚删除操作
    }
};
