<?php

namespace App\Console\Commands;

use App\Models\CollectionTask;
use App\Models\TaskDetail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ResetStuckTasksCommand extends Command
{
    /**
     * 命令名称
     *
     * @var string
     */
    protected $signature = 'tasks:reset-stuck {--hours=2 : 超时小时数}';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '重置卡在进行中状态的任务';

    /**
     * 执行命令
     *
     * @return int
     */
    public function handle()
    {
        $hourThreshold = $this->option('hours');
        $this->info("开始检查卡在进行中状态超过 {$hourThreshold} 小时的任务...");

        // 重置卡住的主任务
        $stuckTasks = CollectionTask::where('status', 1) // 状态1表示进行中
            ->where('started_at', '<', now()->subHours($hourThreshold))
            ->get();

        if ($stuckTasks->count() > 0) {
            foreach ($stuckTasks as $task) {
                $this->info("重置卡住的主任务 ID: {$task->id}");
                
                $task->update([
                    'status' => 3, // 状态3表示失败
                    'error_message' => "任务执行超时，系统自动标记为失败（超过{$hourThreshold}小时）",
                    'completed_at' => now()
                ]);
                
                Log::warning("已重置卡住的主任务", [
                    'task_id' => $task->id,
                    'stuck_hours' => $hourThreshold
                ]);
            }
            
            $this->info("已重置 {$stuckTasks->count()} 个卡住的主任务");
        } else {
            $this->info("没有发现卡住的主任务");
        }

        // 重置卡住的任务详情
        $stuckDetails = TaskDetail::where('status', 1) // 状态1表示进行中
            ->where('started_at', '<', now()->subHours($hourThreshold))
            ->get();

        if ($stuckDetails->count() > 0) {
            foreach ($stuckDetails as $detail) {
                $this->info("重置卡住的任务详情 ID: {$detail->id}");
                
                $detail->update([
                    'status' => 3, // 状态3表示失败
                    'error_message' => "任务执行超时，系统自动标记为失败（超过{$hourThreshold}小时）",
                    'completed_at' => now()
                ]);
                
                // 更新主任务的失败计数
                if ($detail->task) {
                    $detail->task->increment('failed_servers');
                    
                    // 检查主任务是否已完成
                    $totalCompleted = $detail->task->completed_servers + $detail->task->failed_servers;
                    if ($totalCompleted >= $detail->task->total_servers) {
                        $detail->task->update([
                            'status' => 3, // 有失败则标记为失败
                            'completed_at' => now()
                        ]);
                    }
                }
                
                Log::warning("已重置卡住的任务详情", [
                    'detail_id' => $detail->id,
                    'task_id' => $detail->task_id,
                    'stuck_hours' => $hourThreshold
                ]);
            }
            
            $this->info("已重置 {$stuckDetails->count()} 个卡住的任务详情");
        } else {
            $this->info("没有发现卡住的任务详情");
        }

        return Command::SUCCESS;
    }
}