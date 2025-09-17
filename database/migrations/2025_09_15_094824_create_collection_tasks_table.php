<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCollectionTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('collection_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('任务名称');
            $table->string('description', 255)->nullable()->comment('任务描述');
            $table->enum('type', ['single', 'batch'])->default('single')->comment('任务类型：single-单服务器，batch-批量服务器');
            $table->tinyInteger('status')->default(0)->comment('状态：0-未开始，1-进行中，2-已完成，3-失败');
            $table->integer('total_servers')->default(0)->comment('总服务器数量');
            $table->integer('completed_servers')->default(0)->comment('已完成服务器数量');
            $table->integer('failed_servers')->default(0)->comment('失败服务器数量');
            $table->unsignedBigInteger('created_by')->comment('创建人ID');
            $table->timestamp('started_at')->nullable()->comment('开始执行时间');
            $table->timestamp('completed_at')->nullable()->comment('完成时间');
            $table->timestamps();
            
            // 添加索引
            $table->index('status');
            $table->index('created_by');
            $table->index('created_at');
            
            // 添加外键约束
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('collection_tasks');
    }
}
