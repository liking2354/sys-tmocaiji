<?php

namespace App\Jobs;

use App\Models\CollectionTask;
use App\Models\TaskDetail;
use App\Models\CollectionHistory;
use App\Services\CollectionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

class ExecuteBatchCollectionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 任务ID
     *
     * @var int
     */
    protected $taskId;

    /**
     * 任务超时时间（秒）
     *
     * @var int
     */
    public $timeout = 3600; // 1小时

    /**
     * 失败重试次数
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Create a new job instance.
     *
     * @param int $taskId
     * @return void
     */
    public function __construct($taskId)
    {
        $this->taskId = $taskId;
    }

    /**
     * Execute the job.
     *
     * @param CollectionService $collectionService
     * @return void
     */
    public function handle(CollectionService $collectionService)
    {
        $task = CollectionTask::find($this->taskId);
        if (!$task) {
            Log::error('批量采集任务不存在', ['task_id' => $this->taskId]);
            return;
        }

        Log::info('开始执行批量采集任务', ['task_id' => $this->taskId]);

        $pendingDetails = $task->taskDetails()
            ->where('status', 0)
            ->with(['server', 'collector'])
            ->get();

        if ($pendingDetails->isEmpty()) {
            Log::info('没有待执行的任务详情', ['task_id' => $this->taskId]);
            $this->checkTaskCompletion($task);
            return;
        }

        foreach ($pendingDetails as $detail) {
            try {
                // 更新状态为进行中
                $detail->update([
                    'status' => 1,
                    'started_at' => now()
                ]);

                $startTime = microtime(true);
                
                // 执行采集
                $result = $collectionService->executeCollectorScript(
                    $detail->server, 
                    $detail->collector
                );
                
                $endTime = microtime(true);
                $executionTime = round($endTime - $startTime, 3);

                if ($result['success']) {
                    // 更新成功状态
                    $detail->update([
                        'status' => 2,
                        'result' => $result['data'],
                        'execution_time' => $executionTime,
                        'completed_at' => now()
                    ]);

                    // 更新任务统计
                    $task->increment('completed_servers');
                } else {
                    // 更新失败状态
                    $detail->update([
                        'status' => 3,
                        'error_message' => $result['message'],
                        'execution_time' => $executionTime,
                        'completed_at' => now()
                    ]);

                    // 更新任务统计
                    $task->increment('failed_servers');
                }

                // 保存到历史记录
                CollectionHistory::create([
                    'server_id' => $detail->server_id,
                    'collector_id' => $detail->collector_id,
                    'task_detail_id' => $detail->id,
                    'result' => $result['data'] ?? null,
                    'status' => $result['success'] ? 2 : 3,
                    'error_message' => $result['success'] ? null : $result['message'],
                    'execution_time' => $executionTime
                ]);

                Log::info('任务详情执行完成', [
                    'task_id' => $this->taskId,
                    'detail_id' => $detail->id,
                    'success' => $result['success'],
                    'execution_time' => $executionTime
                ]);

            } catch (Exception $e) {
                Log::error('批量采集任务执行失败', [
                    'task_id' => $this->taskId,
                    'detail_id' => $detail->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                $detail->update([
                    'status' => 3,
                    'error_message' => '执行异常：' . $e->getMessage(),
                    'completed_at' => now()
                ]);

                $task->increment('failed_servers');
            }
        }

        // 检查任务是否完成
        $this->checkTaskCompletion($task);
    }

    /**
     * 检查任务是否完成
     *
     * @param CollectionTask $task
     * @return void
     */
    private function checkTaskCompletion(CollectionTask $task)
    {
        $task->refresh();
        
        $totalCompleted = $task->completed_servers + $task->failed_servers;
        
        if ($totalCompleted >= $task->total_servers) {
            $status = $task->failed_servers > 0 ? 3 : 2; // 有失败则标记为失败，否则成功
            
            $task->update([
                'status' => $status,
                'completed_at' => now()
            ]);

            Log::info('批量采集任务完成', [
                'task_id' => $this->taskId,
                'status' => $status,
                'total' => $task->total_servers,
                'completed' => $task->completed_servers,
                'failed' => $task->failed_servers
            ]);
        }
    }

    /**
     * 任务失败时的处理
     *
     * @param Exception $exception
     * @return void
     */
    public function failed(Exception $exception)
    {
        Log::error('批量采集任务队列失败', [
            'task_id' => $this->taskId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        // 更新任务状态为失败
        $task = CollectionTask::find($this->taskId);
        if ($task) {
            $task->update([
                'status' => 3, // 失败
                'completed_at' => now()
            ]);
        }
    }
}
