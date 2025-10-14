<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 记录清理前的统计信息
        $nullPlatformCount = DB::table('cloud_resources')->whereNull('platform_id')->count();
        $orphanedCount = DB::table('cloud_resources')
            ->whereNotNull('platform_id')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('cloud_platforms')
                    ->whereColumn('cloud_platforms.id', 'cloud_resources.platform_id');
            })
            ->count();

        Log::info('Cloud resources cleanup started', [
            'null_platform_count' => $nullPlatformCount,
            'orphaned_count' => $orphanedCount
        ]);

        // 删除platform_id为null的记录
        if ($nullPlatformCount > 0) {
            DB::table('cloud_resources')->whereNull('platform_id')->delete();
            Log::info("Deleted {$nullPlatformCount} cloud resources with null platform_id");
        }

        // 删除platform_id指向不存在平台的记录
        if ($orphanedCount > 0) {
            DB::table('cloud_resources')
                ->whereNotNull('platform_id')
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('cloud_platforms')
                        ->whereColumn('cloud_platforms.id', 'cloud_resources.platform_id');
                })
                ->delete();
            Log::info("Deleted {$orphanedCount} orphaned cloud resources");
        }

        Log::info('Cloud resources cleanup completed');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 无需回滚操作
    }
};