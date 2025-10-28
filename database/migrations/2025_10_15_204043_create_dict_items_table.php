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
        Schema::create('dict_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id')->comment('分类ID');
            $table->string('item_code', 50)->comment('字典项代码');
            $table->string('item_name', 100)->comment('字典项名称');
            $table->string('item_value', 200)->nullable()->comment('字典项值');
            $table->text('description')->nullable()->comment('描述');
            $table->json('extra_data')->nullable()->comment('扩展数据');
            $table->integer('sort_order')->default(0)->comment('排序');
            $table->enum('status', ['active', 'inactive'])->default('active')->comment('状态');
            $table->timestamps();
            
            // 外键约束
            $table->foreign('category_id')->references('id')->on('dict_categories')->onDelete('cascade');
            
            // 索引
            $table->index(['category_id', 'item_code']);
            $table->index('status');
            $table->unique(['category_id', 'item_code'], 'unique_category_item');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dict_items');
    }
};