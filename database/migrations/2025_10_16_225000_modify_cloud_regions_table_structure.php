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
        Schema::table('cloud_regions', function (Blueprint $table) {
            // 删除旧的唯一索引
            $table->dropUnique('cloud_regions_platform_id_region_code_unique');
            
            // 删除旧字段
            $table->dropColumn(['platform_id', 'endpoint', 'metadata']);
            
            // 添加新索引
            $table->unique(['platform_type', 'region_code'], 'uk_platform_region');
            $table->index(['platform_type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cloud_regions', function (Blueprint $table) {
            // 恢复旧字段
            $table->unsignedBigInteger('platform_id')->after('platform_type');
            $table->string('endpoint')->nullable()->after('region_name_en');
            $table->json('metadata')->nullable()->after('sort_order');
            
            // 删除新索引
            $table->dropUnique('uk_platform_region');
            $table->dropIndex(['platform_type', 'is_active']);
            
            // 恢复旧索引
            $table->unique(['platform_id', 'region_code'], 'cloud_regions_platform_id_region_code_unique');
            $table->index(['is_active']);
        });
    }
};