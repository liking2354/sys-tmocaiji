<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('collected_data', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_id')->comment('任务ID');
            $table->unsignedBigInteger('collector_id')->comment('采集器ID');
            $table->string('data_type', 50)->comment('数据类型');
            $table->string('source_url', 1000)->nullable()->comment('源URL');
            $table->json('raw_data')->comment('原始数据');
            $table->json('processed_data')->nullable()->comment('处理后数据');
            $table->string('hash', 64)->nullable()->comment('数据哈希值');
            $table->enum('status', ['raw', 'processed', 'exported', 'archived'])->default('raw')->comment('数据状态');
            $table->timestamp('collected_at')->comment('采集时间');
            $table->timestamp('processed_at')->nullable()->comment('处理时间');
            $table->json('metadata')->nullable()->comment('元数据');
            $table->timestamps();
            
            // 外键约束
            $table->foreign('task_id')->references('id')->on('collection_tasks')->onDelete('cascade');
            $table->foreign('collector_id')->references('id')->on('collectors')->onDelete('cascade');
            
            // 索引
            $table->index(['task_id', 'status']);
            $table->index('collector_id');
            $table->index('data_type');
            $table->index('status');
            $table->index('collected_at');
            $table->index('hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collected_data');
    }
};