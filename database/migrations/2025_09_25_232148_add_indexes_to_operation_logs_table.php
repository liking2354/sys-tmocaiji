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
        Schema::table('operation_logs', function (Blueprint $table) {
            // 为常用查询字段添加索引
            $table->index('user_id', 'idx_operation_logs_user_id');
            $table->index('action', 'idx_operation_logs_action');
            $table->index('ip', 'idx_operation_logs_ip');
            $table->index('created_at', 'idx_operation_logs_created_at');
            
            // 复合索引，用于常见的组合查询
            $table->index(['user_id', 'created_at'], 'idx_operation_logs_user_created');
            $table->index(['action', 'created_at'], 'idx_operation_logs_action_created');
            $table->index(['created_at', 'action'], 'idx_operation_logs_created_action');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('operation_logs', function (Blueprint $table) {
            // 删除索引
            $table->dropIndex('idx_operation_logs_user_id');
            $table->dropIndex('idx_operation_logs_action');
            $table->dropIndex('idx_operation_logs_ip');
            $table->dropIndex('idx_operation_logs_created_at');
            $table->dropIndex('idx_operation_logs_user_created');
            $table->dropIndex('idx_operation_logs_action_created');
            $table->dropIndex('idx_operation_logs_created_action');
        });
    }
};