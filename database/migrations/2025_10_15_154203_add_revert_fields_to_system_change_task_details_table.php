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
        Schema::table('system_change_task_details', function (Blueprint $table) {
            $table->boolean('is_reverted')->default(false)->comment('是否已还原');
            $table->timestamp('reverted_at')->nullable()->comment('还原时间');
            $table->text('revert_log')->nullable()->comment('还原日志');
            $table->string('reverted_by')->nullable()->comment('还原操作人');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('system_change_task_details', function (Blueprint $table) {
            $table->dropColumn(['is_reverted', 'reverted_at', 'revert_log', 'reverted_by']);
        });
    }
};