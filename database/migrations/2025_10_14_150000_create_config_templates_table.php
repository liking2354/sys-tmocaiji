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
        Schema::create('config_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('模板名称');
            $table->text('description')->nullable()->comment('模板描述');
            $table->json('config_items')->comment('配置项列表');
            $table->json('variables')->nullable()->comment('变量定义');
            $table->boolean('is_active')->default(true)->comment('是否启用');
            $table->string('created_by')->nullable()->comment('创建人');
            $table->timestamps();
            
            $table->index(['name', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('config_templates');
    }
};