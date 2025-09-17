<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCollectionHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('collection_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('server_id')->comment('服务器ID');
            $table->unsignedBigInteger('collector_id')->comment('采集组件ID');
            $table->unsignedBigInteger('task_detail_id')->nullable()->comment('关联任务详情ID（可为空，表示单独执行）');
            $table->longText('result')->nullable()->comment('采集结果（JSON格式）');
            $table->tinyInteger('status')->default(2)->comment('状态：2-成功，3-失败');
            $table->text('error_message')->nullable()->comment('错误信息');
            $table->decimal('execution_time', 8, 3)->default(0)->comment('执行时间（秒）');
            $table->timestamps();
            
            // 添加索引
            $table->index('server_id');
            $table->index('collector_id');
            $table->index('created_at');
            
            // 添加外键约束
            $table->foreign('server_id')->references('id')->on('servers')->onDelete('cascade');
            $table->foreign('collector_id')->references('id')->on('collectors')->onDelete('cascade');
            $table->foreign('task_detail_id')->references('id')->on('task_details')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('collection_history');
    }
}
