<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TaskTimeoutService;

class DetectTimeoutTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:detect-timeout {--auto-fix : 自动修复超时任务}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '检测并处理超时的采集任务';

    /**
     * 超时服务
     *
     * @var TaskTimeoutService
     */
    protected $timeoutService;

    /**
     * Create a new command instance.
     *
     * @param TaskTimeoutService $timeoutService
     */
    public function __construct(TaskTimeoutService $timeoutService)
    {
        parent::__construct();
        $this->timeoutService = $timeoutService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('开始检测超时任务...');

        $result = $this->timeoutService->detectAndHandleTimeouts();

        if ($result['success']) {
            $this->info("检测完成！");
            $this->info("发现超时任务: {$result['detected_count']} 个");
            $this->info("处理成功: {$result['processed_count']} 个");

            if (!empty($result['timeout_tasks'])) {
                $this->table(
                    ['任务详情ID', '任务ID', '服务器ID', '采集器ID', '处理动作'],
                    collect($result['timeout_tasks'])->map(function ($task) {
                        return [
                            $task['task_detail_id'],
                            $task['task_id'],
                            $task['server_id'],
                            $task['collector_id'],
                            $task['action'] === 'marked_timeout' ? '标记超时' : '标记失败'
                        ];
                    })->toArray()
                );
            }
        } else {
            $this->error("检测失败: {$result['message']}");
            return 1;
        }

        return 0;
    }
}