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
        Schema::create('cloud_compute_resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cloud_resource_id')->constrained('cloud_resources')->onDelete('cascade');
            
            // 实例基本信息
            $table->string('instance_id')->index();
            $table->string('instance_name')->nullable();
            $table->string('instance_type')->nullable();
            
            // 配置信息
            $table->integer('cpu_cores')->default(0);
            $table->decimal('memory_gb', 8, 2)->default(0);
            $table->string('os_type')->nullable(); // linux, windows, unknown
            $table->string('os_name')->nullable();
            $table->string('image_id')->nullable();
            
            // 网络信息
            $table->string('vpc_id')->nullable();
            $table->string('subnet_id')->nullable();
            $table->json('security_group_ids')->nullable();
            $table->string('public_ip')->nullable();
            $table->string('private_ip')->nullable();
            $table->integer('bandwidth_mbps')->default(0);
            
            // 存储信息
            $table->string('disk_type')->nullable();
            $table->integer('disk_size_gb')->default(0);
            
            // 状态信息
            $table->string('instance_status')->default('unknown');
            $table->string('instance_charge_type')->nullable(); // prepaid, postpaid, spot
            $table->timestamp('expired_time')->nullable();
            $table->timestamp('created_time')->nullable();
            
            // 扩展信息
            $table->json('tags')->nullable();
            $table->boolean('monitoring_enabled')->default(false);
            $table->boolean('auto_scaling_enabled')->default(false);
            
            $table->timestamps();
            
            // 索引
            $table->index(['instance_id', 'instance_status']);
            $table->index('instance_type');
            $table->index('os_type');
            $table->index('instance_charge_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cloud_compute_resources');
    }
};
