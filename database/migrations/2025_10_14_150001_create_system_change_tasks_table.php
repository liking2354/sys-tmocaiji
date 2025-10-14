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
        Schema::create('system_change_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('任务名称');
            $table->text('description')->nullable()->comment('任务描述');
            $table->unsignedBigInteger('server_group_id')->comment('服务器分组ID');
            $table->json('server_ids')->comment('选中的服务器ID列表');
            $table->json('template_ids')->comment('选中的模板ID列表');
            $table->json('config_variables')->nullable()->comment('配置变量值');
            $table->enum('execution_order', ['sequential', 'parallel'])->default('sequential')->comment('执行顺序');
            $table->enum('status', ['draft', 'pending', 'running', 'completed', 'failed', 'paused', 'cancelled'])->default('draft')->comment('任务状态');
            $table->integer('progress')->default(0)->comment('执行进度百分比');
            $table->integer('total_servers')->default(0)->comment('总服务器数量');
            $table->integer('completed_servers')->default(0)->comment('已完成服务器数量');
            $table->integer('failed_servers')->default(0)->comment('失败服务器数量');
            $table->timestamp('scheduled_at')->nullable()->comment('计划执行时间');
            $table->timestamp('started_at')->nullable()->comment('开始执行时间');
            $table->timestamp('completed_at')->nullable()->comment('完成时间');
            $table->string('created_by')->nullable()->comment('创建人');
            $table->timestamps();
            
            $table->foreign('server_group_id')->references('id')->on('server_groups')->onDelete('cascade');
            $table->index(['status', 'created_at']);
            $table->index(['server_group_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('system_change_tasks');
    }
};