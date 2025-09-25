<?php

namespace App\Console\Commands;

use App\Models\CollectionTask;
use App\Models\TaskDetail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DiagnoseTasksCommand extends Command
{
    /**
     * 命令名称
     *
     * @var string
     */
    protected $signature = 'tasks:diagnose {--hours=2 : 超时小时数}';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '诊断任务状态，检查卡住的任务';

    /**
     * 执行命令
     *
     * @return int
     */
    public function handle()
    {
        $hourThreshold = $this->option('hours');
        
        $this->info("=== 任务状态诊断报告 ===");
        $this->info("检查超过 {$hourThreshold} 小时的卡住任务...");
        $this->newLine();

        // 检查数据库连接
        try {
            DB::connection()->getPdo();
            $this->info("✓ 数据库连接正常");
        } catch (\Exception $e) {
            $this->error("✗ 数据库连接失败: " . $e->getMessage());
            return Command::FAILURE;
        }

        // 检查卡住的主任务
        $this->info("1. 检查卡住的主任务:");
        $stuckTasks = CollectionTask::where('status', 1)
            ->where('started_at', '<', now()->subHours($hourThreshold))
            ->get(['id', 'name', 'status', 'started_at', 'total_servers', 'completed_servers', 'failed_servers']);

        if ($stuckTasks->count() > 0) {
            $this->warn("发现 {$stuckTasks->count()} 个卡住的主任务:");
            
            $headers = ['ID', '任务名称', '开始时间', '进度', '状态'];
            $rows = [];
            
            foreach ($stuckTasks as $task) {
                $progress = $task->total_servers > 0 
                    ? round(($task->completed_servers + $task->failed_servers) / $task->total_servers * 100, 1) . '%'
                    : '0%';
                    
                $rows[] = [
                    $task->id,
                    $task->name,
                    $task->started_at->format('Y-m-d H:i:s'),
                    "({$task->completed_servers + $task->failed_servers}/{$task->total_servers}) {$progress}",
                    '进行中'
                ];
            }
            
            $this->table($headers, $rows);
        } else {
            $this->info("✓ 没有发现卡住的主任务");
        }

        $this->newLine();

        // 检查卡住的任务详情
        $this->info("2. 检查卡住的任务详情:");
        $stuckDetails = TaskDetail::where('status', 1)
            ->where('started_at', '<', now()->subHours($hourThreshold))
            ->with(['task:id,name', 'server:id,name,ip'])
            ->get(['id', 'task_id', 'server_id', 'status', 'started_at']);

        if ($stuckDetails->count() > 0) {
            $this->warn("发现 {$stuckDetails->count()} 个卡住的任务详情:");
            
            $headers = ['详情ID', '任务ID', '任务名称', '服务器', '开始时间'];
            $rows = [];
            
            foreach ($stuckDetails as $detail) {
                $serverInfo = $detail->server 
                    ? "{$detail->server->name} ({$detail->server->ip})"
                    : "服务器ID: {$detail->server_id}";
                    
                $taskName = $detail->task ? $detail->task->name : "任务ID: {$detail->task_id}";
                
                $rows[] = [
                    $detail->id,
                    $detail->task_id,
                    $taskName,
                    $serverInfo,
                    $detail->started_at->format('Y-m-d H:i:s')
                ];
            }
            
            $this->table($headers, $rows);
        } else {
            $this->info("✓ 没有发现卡住的任务详情");
        }

        $this->newLine();

        // 统计信息
        $this->info("3. 任务统计信息:");
        
        $totalTasks = CollectionTask::count();
        $runningTasks = CollectionTask::where('status', 1)->count();
        $completedTasks = CollectionTask::where('status', 2)->count();
        $failedTasks = CollectionTask::where('status', 3)->count();
        
        $this->info("总任务数: {$totalTasks}");
        $this->info("进行中: {$runningTasks}");
        $this->info("已完成: {$completedTasks}");
        $this->info("失败: {$failedTasks}");

        $this->newLine();

        // 最近任务执行情况
        $this->info("4. 最近24小时任务执行情况:");
        $recentTasks = CollectionTask::where('created_at', '>', now()->subDay())
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        if ($recentTasks->count() > 0) {
            $statusMap = [0 => '未开始', 1 => '进行中', 2 => '已完成', 3 => '失败'];
            foreach ($recentTasks as $stat) {
                $statusText = $statusMap[$stat->status] ?? '未知';
                $this->info("{$statusText}: {$stat->count} 个");
            }
        } else {
            $this->info("最近24小时内没有任务执行");
        }

        $this->newLine();

        // 建议操作
        if ($stuckTasks->count() > 0 || $stuckDetails->count() > 0) {
            $this->warn("=== 建议操作 ===");
            $this->warn("发现卡住的任务，建议执行以下操作:");
            $this->info("1. 重置卡住的任务: php artisan tasks:reset-stuck --hours={$hourThreshold}");
            $this->info("2. 检查服务器连接状态");
            $this->info("3. 查看应用日志: tail -f storage/logs/laravel.log");
            $this->info("4. 检查定时任务是否正常运行");
            
            if ($this->confirm('是否立即重置卡住的任务?')) {
                $this->call('tasks:reset-stuck', ['--hours' => $hourThreshold]);
            }
        } else {
            $this->info("✓ 所有任务状态正常");
        }

        return Command::SUCCESS;
    }
}