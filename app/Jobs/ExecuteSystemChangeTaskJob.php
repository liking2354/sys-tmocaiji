<?php

namespace App\Jobs;

use App\Models\SystemChangeTask;
use App\Services\SystemChangeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExecuteSystemChangeTaskJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 任务超时时间（秒）
     *
     * @var int
     */
    public $timeout = 3600; // 设置为1小时

    /**
     * 任务尝试次数
     *
     * @var int
     */
    public $tries = 1;

    /**
     * 系统变更任务
     *
     * @var \App\Models\SystemChangeTask
     */
    protected $task;

    /**
     * 创建一个新的任务实例
     *
     * @param  \App\Models\SystemChangeTask  $task
     * @return void
     */
    public function __construct(SystemChangeTask $task)
    {
        $this->task = $task;
    }

    /**
     * 执行任务
     *
     * @param  \App\Services\SystemChangeService  $systemChangeService
     * @return void
     */
    public function handle(SystemChangeService $systemChangeService)
    {
        try {
            Log::info("开始执行系统变更任务队列作业: {$this->task->name} (ID: {$this->task->id})");
            $systemChangeService->executeTask($this->task);
            Log::info("系统变更任务队列作业执行完成: {$this->task->name} (ID: {$this->task->id})");
        } catch (\Exception $e) {
            Log::error("系统变更任务队列作业执行失败: {$e->getMessage()}", [
                'task_id' => $this->task->id,
                'exception' => $e
            ]);
            
            // 更新任务状态为失败
            $this->task->update([
                'status' => SystemChangeTask::STATUS_FAILED,
                'completed_at' => now()
            ]);
            
            throw $e;
        }
    }
}