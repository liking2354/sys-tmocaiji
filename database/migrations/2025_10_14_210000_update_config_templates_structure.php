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
        Schema::table('config_templates', function (Blueprint $table) {
            // 重新设计配置项结构
            $table->dropColumn('config_items');
            
            // 添加新的配置字段
            $table->json('config_rules')->nullable()->comment('配置规则JSON');
            $table->json('template_variables')->nullable()->comment('模板变量定义');
            $table->string('template_type')->default('mixed')->comment('模板类型: directory, file, string, mixed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('config_templates', function (Blueprint $table) {
            $table->dropColumn(['config_rules', 'template_variables', 'template_type']);
            $table->json('config_items')->nullable();
        });
    }
};