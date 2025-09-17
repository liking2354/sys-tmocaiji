<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTypeToCollectorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('collectors', function (Blueprint $table) {
            $table->string('type', 20)->default('script')->after('status')->comment('采集组件类型：script-脚本类，program-程序类');
            $table->string('file_path')->nullable()->after('script_content')->comment('程序类组件的文件路径');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('collectors', function (Blueprint $table) {
            $table->dropColumn('type');
            $table->dropColumn('file_path');
        });
    }
}
