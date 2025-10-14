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
        Schema::create('system_change_task_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_id')->comment('任务ID');
            $table->unsignedBigInteger('server_id')->comment('服务器ID');
            $table->unsignedBigInteger('template_id')->comment('模板ID');
            $table->string('config_file_path', 500)->comment('配置文件路径');
            $table->longText('original_content')->nullable()->comment('原始内容备份');
            $table->longText('new_content')->nullable()->comment('修改后内容');
            $table->enum('status', ['pending', 'running', 'completed', 'failed', 'skipped', 'rolled_back'])->default('pending')->comment('执行状态');
            $table->text('error_message')->nullable()->comment('错误信息');
            $table->text('execution_log')->nullable()->comment('执行日志');
            $table->integer('execution_order')->default(0)->comment('执行顺序');
            $table->boolean('backup_created')->default(false)->comment('是否已创建备份');
            $table->string('backup_path')->nullable()->comment('备份文件路径');
            $table->timestamp('started_at')->nullable()->comment('开始执行时间');
            $table->timestamp('completed_at')->nullable()->comment('完成时间');
            $table->timestamps();
            
            $table->foreign('task_id')->references('id')->on('system_change_tasks')->onDelete('cascade');
            $table->foreign('server_id')->references('id')->on('servers')->onDelete('cascade');
            $table->foreign('template_id')->references('id')->on('config_templates')->onDelete('cascade');
            $table->index(['task_id', 'status']);
            $table->index(['server_id', 'status']);
            $table->index(['task_id', 'execution_order']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('system_change_task_details');
    }
};