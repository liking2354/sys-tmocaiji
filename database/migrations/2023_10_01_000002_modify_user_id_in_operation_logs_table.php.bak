<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyUserIdInOperationLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('operation_logs', function (Blueprint $table) {
            // 修改user_id字段为可为null
            $table->foreignId('user_id')->nullable()->change();
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
            // 恢复user_id字段为不可为null
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
}