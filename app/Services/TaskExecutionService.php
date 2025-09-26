<?php

namespace App\Services;

use App\Models\CollectionTask;
use App\Models\TaskDetail;
use App\Models\CollectionHistory;
use App\Models\Server;
use App\Models\Collector;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

/**
 * 任务执行服务 - 重构版
 * 
 * 解决任务状态同步问题，提供可靠的同步执行机制
 */
class TaskExecutionService
{
    protected $collectionService;

    public function __construct(CollectionService $collectionService)
    {
        $this->collectionService = $collectionService;
    }

    /**
     * 执行批量采集任务（同步执行）
     *
     * @param int $taskId
     * @return array
     */
    public function executeBatchTask($taskId)
    {
        $task = CollectionTask::find($taskId);
        if (!$task) {
            return [
                'success' => false,
                'message' => '任务不存在'
            ];
        }

        Log::info('开始执行批量采集任务', [
            'task_id' => $taskId,
            'task_name' => $task->name
        ]);

        try {
            // 使用数据库事务确保状态一致性
            DB::beginTransaction();

            // 更新任务状态为进行中
            $task->update([
                'status' => 1,
                'started_at' => now(),
                'completed_servers' => 0,
                'failed_servers' => 0
            ]);

            // 获取所有待执行的任务详情
            $taskDetails = $task->taskDetails()
                ->where('status', 0)
                ->with(['server', 'collector'])
                ->get();

            if ($taskDetails->isEmpty()) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => '没有待执行的任务详情'
                ];
            }

            DB::commit();

            // 执行所有任务详情
            $results = $this->executeTaskDetails($task, $taskDetails);

            // 更新最终任务状态
            $this->updateFinalTaskStatus($task);

            return [
                'success' => true,
                'message' => '批量任务执行完成',
                'results' => $results
            ];

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('批量任务执行失败', [
                'task_id' => $taskId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // 更新任务状态为失败
            $task->update([
                'status' => 3,
                'completed_at' => now()
            ]);

            return [
                'success' => false,
                'message' => '任务执行失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 执行任务详情列表
     *
     * @param CollectionTask $task
     * @param \Illuminate\Database\Eloquent\Collection $taskDetails
     * @return array
     */
    protected function executeTaskDetails($task, $taskDetails)
    {
        $results = [];
        $completedCount = 0;
        $failedCount = 0;

        foreach ($taskDetails as $detail) {
            try {
                Log::info('开始执行任务详情', [
                    'task_id' => $task->id,
                    'detail_id' => $detail->id,
                    'server_name' => $detail->server->name,
                    'collector_name' => $detail->collector->name
                ]);

                // 更新详情状态为进行中
                $detail->update([
                    'status' => 1,
                    'started_at' => now()
                ]);

                // 实时更新任务进度
                $this->updateTaskProgress($task);

                $startTime = microtime(true);
                
                // 执行采集
                $result = $this->collectionService->executeCollectorScript(
                    $detail->server,
                    $detail->collector
                );
                
                $endTime = microtime(true);
                $executionTime = round($endTime - $startTime, 3);

                if ($result['success']) {
                    // 成功处理
                    $detail->update([
                        'status' => 2,
                        'result' => $result['data'],
                        'execution_time' => $executionTime,
                        'completed_at' => now()
                    ]);

                    $completedCount++;
                    
                    Log::info('任务详情执行成功', [
                        'task_id' => $task->id,
                        'detail_id' => $detail->id,
                        'execution_time' => $executionTime
                    ]);
                } else {
                    // 失败处理
                    $detail->update([
                        'status' => 3,
                        'error_message' => $result['message'],
                        'execution_time' => $executionTime,
                        'completed_at' => now()
                    ]);

                    $failedCount++;
                    
                    Log::warning('任务详情执行失败', [
                        'task_id' => $task->id,
                        'detail_id' => $detail->id,
                        'error' => $result['message'],
                        'execution_time' => $executionTime
                    ]);
                }

                // 保存到采集历史
                $this->saveCollectionHistory($detail, $result, $executionTime);

                // 实时更新任务统计
                $task->update([
                    'completed_servers' => $completedCount,
                    'failed_servers' => $failedCount
                ]);

                $results[] = [
                    'detail_id' => $detail->id,
                    'server_name' => $detail->server->name,
                    'collector_name' => $detail->collector->name,
                    'success' => $result['success'],
                    'message' => $result['message'],
                    'execution_time' => $executionTime
                ];

            } catch (Exception $e) {
                Log::error('任务详情执行异常', [
                    'task_id' => $task->id,
                    'detail_id' => $detail->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                // 异常处理
                $detail->update([
                    'status' => 3,
                    'error_message' => '执行异常：' . $e->getMessage(),
                    'completed_at' => now()
                ]);

                $failedCount++;
                
                // 更新任务统计
                $task->update([
                    'completed_servers' => $completedCount,
                    'failed_servers' => $failedCount
                ]);

                $results[] = [
                    'detail_id' => $detail->id,
                    'server_name' => $detail->server->name ?? '未知',
                    'collector_name' => $detail->collector->name ?? '未知',
                    'success' => false,
                    'message' => '执行异常：' . $e->getMessage(),
                    'execution_time' => 0
                ];
            }
        }

        return $results;
    }

    /**
     * 保存采集历史
     *
     * @param TaskDetail $detail
     * @param array $result
     * @param float $executionTime
     */
    protected function saveCollectionHistory($detail, $result, $executionTime)
    {
        try {
            CollectionHistory::create([
                'server_id' => $detail->server_id,
                'collector_id' => $detail->collector_id,
                'task_detail_id' => $detail->id,
                'result' => $result['data'] ?? null,
                'status' => $result['success'] ? 2 : 3,
                'error_message' => $result['success'] ? null : $result['message'],
                'execution_time' => $executionTime
            ]);
        } catch (Exception $e) {
            Log::error('保存采集历史失败', [
                'detail_id' => $detail->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 更新任务进度
     *
     * @param CollectionTask $task
     */
    protected function updateTaskProgress($task)
    {
        $task->refresh();
        
        // 计算实时进度
        $totalDetails = $task->taskDetails()->count();
        $completedDetails = $task->taskDetails()->whereIn('status', [2, 3])->count();
        $runningDetails = $task->taskDetails()->where('status', 1)->count();
        
        Log::info('任务进度更新', [
            'task_id' => $task->id,
            'total' => $totalDetails,
            'completed' => $completedDetails,
            'running' => $runningDetails,
            'progress' => $totalDetails > 0 ? round($completedDetails / $totalDetails * 100, 2) : 0
        ]);
    }

    /**
     * 更新最终任务状态
     *
     * @param CollectionTask $task
     */
    protected function updateFinalTaskStatus($task)
    {
        $task->refresh();
        
        $totalDetails = $task->taskDetails()->count();
        $completedDetails = $task->taskDetails()->where('status', 2)->count();
        $failedDetails = $task->taskDetails()->where('status', 3)->count();
        $finishedDetails = $completedDetails + $failedDetails;

        // 确定最终状态
        if ($finishedDetails >= $totalDetails) {
            $finalStatus = $failedDetails > 0 ? 3 : 2; // 有失败则为失败，否则为成功
        } else {
            $finalStatus = 1; // 仍在进行中
        }

        $task->update([
            'status' => $finalStatus,
            'total_servers' => $totalDetails,
            'completed_servers' => $completedDetails,
            'failed_servers' => $failedDetails,
            'completed_at' => $finalStatus != 1 ? now() : null
        ]);

        Log::info('任务最终状态更新', [
            'task_id' => $task->id,
            'final_status' => $finalStatus,
            'total' => $totalDetails,
            'completed' => $completedDetails,
            'failed' => $failedDetails
        ]);
    }

    /**
     * 重置任务状态
     *
     * @param int $taskId
     * @return array
     */
    public function resetTask($taskId)
    {
        $task = CollectionTask::find($taskId);
        if (!$task) {
            return [
                'success' => false,
                'message' => '任务不存在'
            ];
        }

        try {
            DB::beginTransaction();

            // 重置主任务状态
            $task->update([
                'status' => 0,
                'completed_servers' => 0,
                'failed_servers' => 0,
                'started_at' => null,
                'completed_at' => null
            ]);

            // 重置所有任务详情状态
            $task->taskDetails()->update([
                'status' => 0,
                'result' => null,
                'error_message' => null,
                'execution_time' => 0,
                'started_at' => null,
                'completed_at' => null
            ]);

            DB::commit();

            Log::info('任务状态重置成功', ['task_id' => $taskId]);

            return [
                'success' => true,
                'message' => '任务状态已重置'
            ];

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('任务状态重置失败', [
                'task_id' => $taskId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => '重置失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 取消正在执行的任务
     *
     * @param int $taskId
     * @return array
     */
    public function cancelTask($taskId)
    {
        $task = CollectionTask::find($taskId);
        if (!$task) {
            return [
                'success' => false,
                'message' => '任务不存在'
            ];
        }

        if (!$task->isRunning()) {
            return [
                'success' => false,
                'message' => '只能取消正在执行的任务'
            ];
        }

        try {
            DB::beginTransaction();

            // 更新主任务状态为失败
            $task->update([
                'status' => 3,
                'completed_at' => now()
            ]);

            // 更新所有未完成的任务详情状态
            $task->taskDetails()
                ->whereIn('status', [0, 1])
                ->update([
                    'status' => 3,
                    'error_message' => '任务被用户取消',
                    'completed_at' => now()
                ]);

            DB::commit();

            Log::info('任务取消成功', ['task_id' => $taskId]);

            return [
                'success' => true,
                'message' => '任务已取消'
            ];

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('任务取消失败', [
                'task_id' => $taskId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => '取消失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 获取任务实时状态
     *
     * @param int $taskId
     * @return array
     */
    public function getTaskStatus($taskId)
    {
        $task = CollectionTask::find($taskId);
        if (!$task) {
            return [
                'success' => false,
                'message' => '任务不存在'
            ];
        }

        $task->load('taskDetails');
        
        $statusCounts = $task->taskDetails->groupBy('status')->map(function ($items) {
            return $items->count();
        });

        $totalDetails = $task->taskDetails->count();
        $completedDetails = $statusCounts->get(2, 0) + $statusCounts->get(3, 0);
        $progress = $totalDetails > 0 ? round($completedDetails / $totalDetails * 100, 2) : 0;

        return [
            'success' => true,
            'data' => [
                'task_id' => $task->id,
                'name' => $task->name,
                'status' => $task->status,
                'status_text' => $task->statusText,
                'progress' => $progress,
                'total' => $totalDetails,
                'pending' => $statusCounts->get(0, 0),
                'running' => $statusCounts->get(1, 0),
                'completed' => $statusCounts->get(2, 0),
                'failed' => $statusCounts->get(3, 0),
                'started_at' => $task->started_at,
                'completed_at' => $task->completed_at,
                'created_at' => $task->created_at,
                'updated_at' => $task->updated_at
            ]
        ];
    }
}