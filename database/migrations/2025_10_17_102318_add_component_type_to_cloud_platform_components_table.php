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
        Schema::table('cloud_platform_components', function (Blueprint $table) {
            $table->string('component_type')->after('component_dict_id')->nullable()->comment('组件类型标识');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cloud_platform_components', function (Blueprint $table) {
            $table->dropColumn('component_type');
        });
    }
};
