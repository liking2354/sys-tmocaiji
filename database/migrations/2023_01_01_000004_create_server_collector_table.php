<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServerCollectorTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('server_collector', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained('servers')->onDelete('cascade');
            $table->foreignId('collector_id')->constrained('collectors')->onDelete('cascade');
            $table->timestamp('installed_at')->nullable();
            $table->tinyInteger('status')->default(0)->comment('0=未安装, 1=已安装');
            $table->timestamps();
            
            // 确保一个服务器不会重复关联同一个采集组件
            $table->unique(['server_id', 'collector_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('server_collector');
    }
}