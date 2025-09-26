<?php

namespace App\Console\Commands;

use App\Models\CollectionTask;
use App\Models\TaskDetail;
use App\Models\CollectionHistory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixTaskStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:fix-status {--dry-run : 只显示需要修复的任务，不实际修复}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '修复任务状态不一致的问题';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        $this->info('开始检查任务状态一致性...');
        
        // 获取所有进行中的任务
        $runningTasks = CollectionTask::where('status', 1)->get();
        
        $fixedCount = 0;
        
        foreach ($runningTasks as $task) {
            $this->info("检查任务 ID: {$task->id} - {$task->name}");
            
            // 统计任务详情状态
            $detailStats = $task->taskDetails()
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();
            
            $totalDetails = array_sum($detailStats);
            $completedDetails = ($detailStats[2] ?? 0); // 已完成
            $failedDetails = ($detailStats[3] ?? 0);     // 失败
            $runningDetails = ($detailStats[1] ?? 0);    // 进行中
            $pendingDetails = ($detailStats[0] ?? 0);    // 未开始
            
            $this->line("  任务详情统计: 总计={$totalDetails}, 未开始={$pendingDetails}, 进行中={$runningDetails}, 已完成={$completedDetails}, 失败={$failedDetails}");
            
            // 检查是否需要修复
            $needsFix = false;
            $newStatus = 1; // 默认保持进行中
            
            // 如果所有任务详情都已完成或失败
            if ($pendingDetails == 0 && $runningDetails == 0) {
                $needsFix = true;
                $newStatus = $failedDetails > 0 ? 3 : 2; // 有失败则标记为失败，否则成功
                $this->warn("  ❌ 任务状态不一致: 所有子任务已完成，但主任务仍为进行中");
            }
            
            // 检查统计数据是否正确
            if ($task->total_servers != $totalDetails) {
                $this->warn("  ❌ 总任务数不匹配: 数据库={$task->total_servers}, 实际={$totalDetails}");
                $needsFix = true;
            }
            
            if ($task->completed_servers != $completedDetails) {
                $this->warn("  ❌ 已完成数不匹配: 数据库={$task->completed_servers}, 实际={$completedDetails}");
                $needsFix = true;
            }
            
            if ($task->failed_servers != $failedDetails) {
                $this->warn("  ❌ 失败数不匹配: 数据库={$task->failed_servers}, 实际={$failedDetails}");
                $needsFix = true;
            }
            
            if ($needsFix) {
                if ($dryRun) {
                    $this->line("  🔧 [DRY RUN] 需要修复:");
                    $this->line("    - 状态: {$task->status} -> {$newStatus}");
                    $this->line("    - 总数: {$task->total_servers} -> {$totalDetails}");
                    $this->line("    - 完成: {$task->completed_servers} -> {$completedDetails}");
                    $this->line("    - 失败: {$task->failed_servers} -> {$failedDetails}");
                } else {
                    $this->line("  🔧 正在修复...");
                    
                    $updateData = [
                        'status' => $newStatus,
                        'total_servers' => $totalDetails,
                        'completed_servers' => $completedDetails,
                        'failed_servers' => $failedDetails,
                    ];
                    
                    // 如果任务完成，设置完成时间
                    if ($newStatus != 1 && !$task->completed_at) {
                        $updateData['completed_at'] = now();
                    }
                    
                    $task->update($updateData);
                    
                    $this->info("  ✅ 修复完成");
                }
                
                $fixedCount++;
            } else {
                $this->info("  ✅ 状态正常");
            }
            
            $this->line("");
        }
        
        // 检查孤立的任务详情（没有对应采集历史的已完成任务）
        $this->info('检查孤立的任务详情...');
        
        $orphanedDetails = TaskDetail::where('status', 2)
            ->whereDoesntHave('collectionHistories')
            ->with(['task', 'server', 'collector'])
            ->get();
        
        if ($orphanedDetails->count() > 0) {
            $this->warn("发现 {$orphanedDetails->count()} 个孤立的任务详情（已完成但没有采集历史）");
            
            foreach ($orphanedDetails as $detail) {
                $this->line("  任务详情 ID: {$detail->id} (任务: {$detail->task->name})");
                
                if (!$dryRun) {
                    // 创建对应的采集历史记录
                    CollectionHistory::create([
                        'server_id' => $detail->server_id,
                        'collector_id' => $detail->collector_id,
                        'task_detail_id' => $detail->id,
                        'result' => $detail->result,
                        'status' => $detail->status,
                        'error_message' => $detail->error_message,
                        'execution_time' => $detail->execution_time ?? 0,
                        'created_at' => $detail->completed_at ?? $detail->updated_at,
                        'updated_at' => $detail->completed_at ?? $detail->updated_at,
                    ]);
                    
                    $this->info("  ✅ 已创建对应的采集历史记录");
                }
            }
        }
        
        if ($dryRun) {
            $this->info("DRY RUN 模式: 发现 {$fixedCount} 个需要修复的任务");
            $this->info("运行 php artisan tasks:fix-status 来实际修复这些问题");
        } else {
            $this->info("修复完成! 共修复了 {$fixedCount} 个任务");
        }
        
        return 0;
    }
}