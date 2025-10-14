<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddTimeoutStatusToTaskDetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('task_details', function (Blueprint $table) {
            // 添加超时时间字段
            $table->timestamp('timeout_at')->nullable()->after('completed_at')->comment('超时时间');
            // 添加重试次数字段
            $table->tinyInteger('retry_count')->default(0)->after('timeout_at')->comment('重试次数');
            // 添加最大重试次数字段
            $table->tinyInteger('max_retries')->default(3)->after('retry_count')->comment('最大重试次数');
        });
        
        // 更新状态注释，添加超时状态
        DB::statement("ALTER TABLE task_details MODIFY COLUMN status TINYINT NOT NULL DEFAULT 0 COMMENT '状态：0-未开始，1-进行中，2-已完成，3-失败，4-超时'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('task_details', function (Blueprint $table) {
            $table->dropColumn(['timeout_at', 'retry_count', 'max_retries']);
        });
        
        // 恢复原来的状态注释
        DB::statement("ALTER TABLE task_details MODIFY COLUMN status TINYINT NOT NULL DEFAULT 0 COMMENT '状态：0-未开始，1-进行中，2-已完成，3-失败'");
    }
}