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
            $table->string('rule_type', 50)->nullable()->after('config_file_path')->comment('规则类型');
            $table->json('rule_data')->nullable()->after('rule_type')->comment('规则数据');
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
            $table->dropColumn(['rule_type', 'rule_data']);
        });
    }
};
