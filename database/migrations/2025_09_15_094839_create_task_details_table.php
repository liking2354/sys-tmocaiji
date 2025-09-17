<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTaskDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('task_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_id')->comment('任务ID');
            $table->unsignedBigInteger('server_id')->comment('服务器ID');
            $table->unsignedBigInteger('collector_id')->comment('采集组件ID');
            $table->tinyInteger('status')->default(0)->comment('状态：0-未开始，1-进行中，2-已完成，3-失败');
            $table->longText('result')->nullable()->comment('采集结果（JSON格式）');
            $table->text('error_message')->nullable()->comment('错误信息');
            $table->decimal('execution_time', 8, 3)->default(0)->comment('执行时间（秒）');
            $table->timestamp('started_at')->nullable()->comment('开始执行时间');
            $table->timestamp('completed_at')->nullable()->comment('完成时间');
            $table->timestamps();
            
            // 添加索引
            $table->index('task_id');
            $table->index('server_id');
            $table->index('collector_id');
            $table->index('status');
            
            // 添加外键约束
            $table->foreign('task_id')->references('id')->on('collection_tasks')->onDelete('cascade');
            $table->foreign('server_id')->references('id')->on('servers')->onDelete('cascade');
            $table->foreign('collector_id')->references('id')->on('collectors')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('task_details');
    }
}
