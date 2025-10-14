<?php

namespace App\Services;

use App\Models\TaskDetail;
use App\Models\CollectionTask;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TaskTimeoutService
{
    /**
     * 默认超时时间（分钟）
     */
    const DEFAULT_TIMEOUT_MINUTES = 3;

    /**
     * 检测并处理超时任务
     *
     * @return array
     */
    public function detectAndHandleTimeouts()
    {
        $timeoutTasks = [];
        $processedCount = 0;

        try {
            // 查找超时的任务详情
            $timeoutDetails = $this->findTimeoutTaskDetails();
            
            foreach ($timeoutDetails as $detail) {
                $result = $this->handleTimeoutTask($detail);
                if ($result['success']) {
                    $timeoutTasks[] = [
                        'task_detail_id' => $detail->id,
                        'task_id' => $detail->task_id,
                        'server_id' => $detail->server_id,
                        'collector_id' => $detail->collector_id,
                        'action' => $result['action']
                    ];
                    $processedCount++;
                }
            }

            // 更新相关任务的统计信息
            $this->updateTaskStatistics($timeoutDetails);

            Log::info('超时任务检测完成', [
                'detected_count' => count($timeoutDetails),
                'processed_count' => $processedCount,
                'timeout_tasks' => $timeoutTasks
            ]);

            return [
                'success' => true,
                'detected_count' => count($timeoutDetails),
                'processed_count' => $processedCount,
                'timeout_tasks' => $timeoutTasks
            ];

        } catch (\Exception $e) {
            Log::error('超时任务检测失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => '超时检测失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 查找超时的任务详情
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function findTimeoutTaskDetails()
    {
        $timeoutThreshold = Carbon::now()->subMinutes(self::DEFAULT_TIMEOUT_MINUTES);

        return TaskDetail::whereIn('status', [0, 1]) // 未开始或进行中状态
            ->where('updated_at', '<', $timeoutThreshold) // 更新时间超过阈值
            ->with(['task', 'server', 'collector'])
            ->get();
    }

    /**
     * 处理单个超时任务
     *
     * @param TaskDetail $detail
     * @return array
     */
    protected function handleTimeoutTask(TaskDetail $detail)
    {
        try {
            DB::beginTransaction();

            // 检查是否还能重试
            if ($detail->retry_count < $detail->max_retries) {
                // 标记为超时，但不增加重试次数（等待手动重试）
                $detail->update([
                    'status' => 4, // 超时状态
                    'timeout_at' => Carbon::now(),
                    'error_message' => "任务执行超时（超过 " . self::DEFAULT_TIMEOUT_MINUTES . " 分钟），可以重新执行"
                ]);

                $action = 'marked_timeout';
                $message = '标记为超时，等待重新执行';
            } else {
                // 超过最大重试次数，标记为失败
                $detail->update([
                    'status' => 3, // 失败状态
                    'completed_at' => Carbon::now(),
                    'error_message' => "任务执行超时且已达到最大重试次数（{$detail->max_retries}次）"
                ]);

                $action = 'marked_failed';
                $message = '超过最大重试次数，标记为失败';
            }

            DB::commit();

            Log::info('处理超时任务', [
                'task_detail_id' => $detail->id,
                'task_id' => $detail->task_id,
                'server_id' => $detail->server_id,
                'collector_id' => $detail->collector_id,
                'action' => $action,
                'retry_count' => $detail->retry_count,
                'max_retries' => $detail->max_retries
            ]);

            return [
                'success' => true,
                'action' => $action,
                'message' => $message
            ];

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('处理超时任务失败', [
                'task_detail_id' => $detail->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => '处理失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 重新执行单个任务详情
     *
     * @param int $taskDetailId
     * @return array
     */
    public function retryTaskDetail($taskDetailId)
    {
        try {
            $detail = TaskDetail::with(['task', 'server', 'collector'])->findOrFail($taskDetailId);

            // 检查任务状态
            if (!in_array($detail->status, [3, 4])) { // 只能重试失败或超时的任务
                return [
                    'success' => false,
                    'message' => '只能重新执行失败或超时的任务'
                ];
            }

            // 检查重试次数
            if ($detail->retry_count >= $detail->max_retries) {
                return [
                    'success' => false,
                    'message' => "已达到最大重试次数（{$detail->max_retries}次）"
                ];
            }

            // 检查服务器和采集器状态
            if (!$detail->server || $detail->server->status != 1) {
                return [
                    'success' => false,
                    'message' => '服务器不可用'
                ];
            }

            if (!$detail->collector || $detail->collector->status != 1) {
                return [
                    'success' => false,
                    'message' => '采集组件不可用'
                ];
            }

            DB::beginTransaction();

            // 重置任务状态
            $detail->update([
                'status' => 0, // 重置为未开始
                'retry_count' => $detail->retry_count + 1,
                'result' => null,
                'error_message' => null,
                'execution_time' => 0,
                'started_at' => null,
                'completed_at' => null,
                'timeout_at' => null
            ]);

            // 更新任务统计
            $this->updateSingleTaskStatistics($detail->task_id);

            DB::commit();

            // 异步执行任务
            $this->executeTaskDetailAsync($detail);

            Log::info('重新执行任务详情', [
                'task_detail_id' => $detail->id,
                'task_id' => $detail->task_id,
                'server_id' => $detail->server_id,
                'collector_id' => $detail->collector_id,
                'retry_count' => $detail->retry_count
            ]);

            return [
                'success' => true,
                'message' => '任务已重新开始执行',
                'data' => [
                    'task_detail_id' => $detail->id,
                    'retry_count' => $detail->retry_count,
                    'max_retries' => $detail->max_retries
                ]
            ];

        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollback();
            }

            Log::error('重新执行任务详情失败', [
                'task_detail_id' => $taskDetailId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => '重新执行失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 异步执行任务详情
     *
     * @param TaskDetail $detail
     */
    protected function executeTaskDetailAsync(TaskDetail $detail)
    {
        // 这里可以使用队列系统异步执行
        // 暂时使用同步执行作为示例
        try {
            $collectionService = app(\App\Services\CollectionService::class);
            $collectionService->executeSingleTaskDetail($detail);
        } catch (\Exception $e) {
            Log::error('异步执行任务详情失败', [
                'task_detail_id' => $detail->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 更新任务统计信息
     *
     * @param \Illuminate\Database\Eloquent\Collection $timeoutDetails
     */
    protected function updateTaskStatistics($timeoutDetails)
    {
        $taskIds = $timeoutDetails->pluck('task_id')->unique();

        foreach ($taskIds as $taskId) {
            $this->updateSingleTaskStatistics($taskId);
        }
    }

    /**
     * 更新单个任务的统计信息
     *
     * @param int $taskId
     */
    protected function updateSingleTaskStatistics($taskId)
    {
        $task = CollectionTask::find($taskId);
        if (!$task) {
            return;
        }

        $stats = TaskDetail::where('task_id', $taskId)
            ->selectRaw('
                COUNT(*) as total_count,
                COUNT(CASE WHEN status = 0 THEN 1 END) as pending_count,
                COUNT(CASE WHEN status = 1 THEN 1 END) as running_count,
                COUNT(CASE WHEN status = 2 THEN 1 END) as completed_count,
                COUNT(CASE WHEN status = 3 THEN 1 END) as failed_count,
                COUNT(CASE WHEN status = 4 THEN 1 END) as timeout_count
            ')
            ->first();

        // 计算新的任务状态
        $newStatus = $task->status;
        $completedAt = $task->completed_at;

        // 如果所有任务都已完成（成功、失败或超时），则标记整个任务为完成
        if ($stats->pending_count == 0 && $stats->running_count == 0) {
            if ($stats->completed_count == $stats->total_count) {
                // 全部成功
                $newStatus = 2; // 已完成
            } else {
                // 有失败或超时的任务，但所有任务都已结束
                $newStatus = 2; // 已完成（包含部分失败）
            }
            
            // 如果状态发生变化，设置完成时间
            if ($newStatus != $task->status) {
                $completedAt = now();
            }
        } elseif ($stats->running_count > 0 || $stats->pending_count > 0) {
            // 还有任务在进行中或未开始
            $newStatus = 1; // 进行中
            $completedAt = null; // 清除完成时间
        }

        // 更新任务统计和状态
        $updateData = [
            'completed_servers' => $stats->completed_count,
            'failed_servers' => $stats->failed_count + $stats->timeout_count, // 超时也算失败
            'status' => $newStatus
        ];

        // 只有在状态变化时才更新完成时间
        if ($completedAt !== $task->completed_at) {
            $updateData['completed_at'] = $completedAt;
        }

        $task->update($updateData);

        Log::info('任务统计信息已更新', [
            'task_id' => $taskId,
            'total' => $stats->total_count,
            'pending' => $stats->pending_count,
            'running' => $stats->running_count,
            'completed' => $stats->completed_count,
            'failed' => $stats->failed_count,
            'timeout' => $stats->timeout_count,
            'old_status' => $task->status,
            'new_status' => $newStatus,
            'completed_at' => $completedAt
        ]);
    }

    /**
     * 获取任务超时统计
     *
     * @param int $taskId
     * @return array
     */
    public function getTaskTimeoutStats($taskId)
    {
        $stats = TaskDetail::where('task_id', $taskId)
            ->selectRaw('
                COUNT(*) as total,
                COUNT(CASE WHEN status = 0 THEN 1 END) as pending,
                COUNT(CASE WHEN status = 1 THEN 1 END) as running,
                COUNT(CASE WHEN status = 2 THEN 1 END) as completed,
                COUNT(CASE WHEN status = 3 THEN 1 END) as failed,
                COUNT(CASE WHEN status = 4 THEN 1 END) as timeout
            ')
            ->first();

        return [
            'total' => $stats->total,
            'pending' => $stats->pending,
            'running' => $stats->running,
            'completed' => $stats->completed,
            'failed' => $stats->failed,
            'timeout' => $stats->timeout,
            'can_retry' => $stats->failed + $stats->timeout
        ];
    }
}