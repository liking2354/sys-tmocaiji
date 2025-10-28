<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CloudResourceManagementService;

class ExecuteSyncTask extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:execute-task {taskId : 同步任务ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '执行云资源同步任务';

    /**
     * Execute the console command.
     */
    public function handle(CloudResourceManagementService $service)
    {
        $taskId = $this->argument('taskId');
        
        $this->info("开始执行同步任务: {$taskId}");
        
        try {
            $service->executeTask($taskId);
            $this->info("同步任务执行完成: {$taskId}");
        } catch (\Exception $e) {
            $this->error("同步任务执行失败: {$e->getMessage()}");
            return 1;
        }
        
        return 0;
    }
}