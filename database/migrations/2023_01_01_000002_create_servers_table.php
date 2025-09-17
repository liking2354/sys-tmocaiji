<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('servers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('server_groups')->onDelete('cascade');
            $table->string('name', 50);
            $table->string('ip', 15);
            $table->integer('port')->default(22);
            $table->string('username', 50);
            $table->string('password', 255);
            $table->tinyInteger('status')->default(0)->comment('0-离线，1-在线');
            $table->timestamp('last_check_time')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('servers');
    }
}