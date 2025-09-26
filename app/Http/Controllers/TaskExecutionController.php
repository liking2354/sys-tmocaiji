<?php

namespace App\Http\Controllers;

use App\Models\CollectionTask;
use App\Services\TaskExecutionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * 任务执行控制器 - 重构版
 * 
 * 专门处理任务执行相关的操作
 */
class TaskExecutionController extends Controller
{
    protected $taskExecutionService;

    public function __construct(TaskExecutionService $taskExecutionService)
    {
        $this->taskExecutionService = $taskExecutionService;
    }

    /**
     * 执行批量任务
     *
     * @param Request $request
     * @param int $taskId
     * @return \Illuminate\Http\JsonResponse
     */
    public function executeBatchTask(Request $request, $taskId)
    {
        try {
            $result = $this->taskExecutionService->executeBatchTask($taskId);
            
            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'data' => $result['results'] ?? null
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('执行批量任务失败', [
                'task_id' => $taskId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => '执行失败：' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 重置任务状态
     *
     * @param Request $request
     * @param int $taskId
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetTask(Request $request, $taskId)
    {
        try {
            $result = $this->taskExecutionService->resetTask($taskId);
            
            return response()->json($result, $result['success'] ? 200 : 400);
        } catch (\Exception $e) {
            Log::error('重置任务失败', [
                'task_id' => $taskId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => '重置失败：' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 取消任务
     *
     * @param Request $request
     * @param int $taskId
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelTask(Request $request, $taskId)
    {
        try {
            $result = $this->taskExecutionService->cancelTask($taskId);
            
            return response()->json($result, $result['success'] ? 200 : 400);
        } catch (\Exception $e) {
            Log::error('取消任务失败', [
                'task_id' => $taskId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => '取消失败：' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 获取任务状态
     *
     * @param Request $request
     * @param int $taskId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTaskStatus(Request $request, $taskId)
    {
        try {
            $result = $this->taskExecutionService->getTaskStatus($taskId);
            
            return response()->json($result, $result['success'] ? 200 : 404);
        } catch (\Exception $e) {
            Log::error('获取任务状态失败', [
                'task_id' => $taskId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => '获取状态失败：' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 批量执行多个任务
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function executeBatchTasks(Request $request)
    {
        $request->validate([
            'task_ids' => 'required|array',
            'task_ids.*' => 'integer|exists:collection_tasks,id'
        ]);

        $taskIds = $request->input('task_ids');
        $results = [];
        $successCount = 0;
        $failedCount = 0;

        foreach ($taskIds as $taskId) {
            try {
                $result = $this->taskExecutionService->executeBatchTask($taskId);
                
                if ($result['success']) {
                    $successCount++;
                } else {
                    $failedCount++;
                }

                $results[] = [
                    'task_id' => $taskId,
                    'success' => $result['success'],
                    'message' => $result['message']
                ];
            } catch (\Exception $e) {
                $failedCount++;
                $results[] = [
                    'task_id' => $taskId,
                    'success' => false,
                    'message' => '执行异常：' . $e->getMessage()
                ];
            }
        }

        return response()->json([
            'success' => $failedCount == 0,
            'message' => "批量执行完成：成功 {$successCount} 个，失败 {$failedCount} 个",
            'results' => $results,
            'summary' => [
                'total' => count($taskIds),
                'success' => $successCount,
                'failed' => $failedCount
            ]
        ]);
    }

    /**
     * 获取多个任务的状态
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBatchTaskStatus(Request $request)
    {
        $request->validate([
            'task_ids' => 'required|array',
            'task_ids.*' => 'integer'
        ]);

        $taskIds = $request->input('task_ids');
        $results = [];

        foreach ($taskIds as $taskId) {
            try {
                $result = $this->taskExecutionService->getTaskStatus($taskId);
                $results[] = $result['success'] ? $result['data'] : [
                    'task_id' => $taskId,
                    'error' => $result['message']
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'task_id' => $taskId,
                    'error' => '获取状态失败：' . $e->getMessage()
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => $results
        ]);
    }
}