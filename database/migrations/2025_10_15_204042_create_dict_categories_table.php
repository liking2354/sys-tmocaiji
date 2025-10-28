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
        Schema::create('dict_categories', function (Blueprint $table) {
            $table->id();
            $table->string('category_code', 50)->unique()->comment('分类代码');
            $table->string('category_name', 100)->comment('分类名称');
            $table->text('description')->nullable()->comment('分类描述');
            $table->integer('sort_order')->default(0)->comment('排序');
            $table->enum('status', ['active', 'inactive'])->default('active')->comment('状态');
            $table->timestamps();
            
            // 索引
            $table->index('category_code');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dict_categories');
    }
};
