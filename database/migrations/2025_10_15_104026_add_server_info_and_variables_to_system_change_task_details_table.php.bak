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
        Schema::table('system_change_task_details', function (Blueprint $table) {
            $table->string('server_ip', 45)->nullable()->after('server_id')->comment('服务器IP地址');
            $table->string('server_name')->nullable()->after('server_ip')->comment('服务器名称');
            $table->json('config_variables')->nullable()->after('rule_data')->comment('配置变量');
            $table->text('target_path')->nullable()->after('config_variables')->comment('目标路径');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('system_change_task_details', function (Blueprint $table) {
            $table->dropColumn(['server_ip', 'server_name', 'config_variables', 'target_path']);
        });
    }
};