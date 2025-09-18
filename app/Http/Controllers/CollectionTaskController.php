<?php

namespace App\Http\Controllers;

use App\Models\CollectionTask;
use App\Models\TaskDetail;
use App\Models\Server;
use App\Models\Collector;
use App\Services\CollectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class CollectionTaskController extends Controller
{
    /**
     * 采集服务
     *
     * @var CollectionService
     */
    protected $collectionService;

    /**
     * 构造函数
     *
     * @param CollectionService $collectionService
     */
    public function __construct(CollectionService $collectionService)
    {
        $this->collectionService = $collectionService;
    }

    /**
     * 任务列表
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = CollectionTask::with('creator');
        
        // 搜索条件
        if ($request->has('search') && !empty($request->input('search'))) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%");
        }
        
        // 状态筛选
        if ($request->has('status') && $request->input('status') !== '') {
            $query->where('status', $request->input('status'));
        }
        
        // 类型筛选
        if ($request->has('type') && !empty($request->input('type'))) {
            $query->where('type', $request->input('type'));
        }
        
        $tasks = $query->orderBy('created_at', 'desc')->paginate(15);
        
        return view('collection-tasks.index', compact('tasks'));
    }

    /**
     * 创建单服务器采集任务页面
     *
     * @param Server $server
     * @return \Illuminate\Http\Response
     */
    public function createSingle(Server $server)
    {
        $collectors = $server->collectors()->where('status', 1)->get();
        
        if ($collectors->isEmpty()) {
            return redirect()->route('servers.show', $server)
                ->with('warning', '该服务器没有可用的采集组件，请先安装采集组件');
        }
        
        return view('collection-tasks.create-single', compact('server', 'collectors'));
    }

    /**
     * 创建批量采集任务页面
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function createBatch(Request $request)
    {
        $serverIds = $request->input('server_ids', []);
        
        if (empty($serverIds)) {
            return redirect()->route('servers.index')
                ->with('error', '请先选择要执行采集的服务器');
        }
        
        $servers = Server::whereIn('id', $serverIds)->with('collectors')->get();
        
        // 获取所有服务器共同的采集组件
        $commonCollectors = $this->collectionService->getCommonCollectors($serverIds);
        
        if (empty($commonCollectors)) {
            return redirect()->route('servers.index')
                ->with('warning', '选中的服务器没有共同的采集组件');
        }
        
        return view('collection-tasks.create-batch', compact('servers', 'commonCollectors'));
    }

    /**
     * 执行单服务器采集任务
     *
     * @param Request $request
     * @param Server $server
     * @return \Illuminate\Http\JsonResponse
     */
    public function executeSingle(Request $request, Server $server)
    {
        $validator = Validator::make($request->all(), [
            'collector_ids' => 'required|array|min:1',
            'collector_ids.*' => 'exists:collectors,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '参数验证失败',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->collectionService->executeSingleCollection(
            $server, 
            $request->input('collector_ids')
        );

        return response()->json($result);
    }

    /**
     * 执行批量采集任务
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function executeBatch(Request $request)
    {
        // 处理server_ids，如果是逗号分隔的字符串，转换为数组
        $serverIds = $request->input('server_ids');
        if (is_string($serverIds)) {
            $serverIds = array_filter(explode(',', $serverIds));
        }
        
        // 处理collector_ids，确保是数组
        $collectorIds = $request->input('collector_ids', []);
        if (!is_array($collectorIds)) {
            $collectorIds = [$collectorIds];
        }
        
        $validator = Validator::make([
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'server_ids' => $serverIds,
            'collector_ids' => $collectorIds
        ], [
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'server_ids' => 'required|array|min:1',
            'server_ids.*' => 'exists:servers,id',
            'collector_ids' => 'required|array|min:1',
            'collector_ids.*' => 'exists:collectors,id'
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => '参数验证失败',
                    'errors' => $validator->errors()
                ], 422);
            }
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $result = $this->collectionService->executeBatchCollection(
            $request->input('name'),
            $request->input('description') ?? '',
            $serverIds,
            $collectorIds
        );

        if ($result['success']) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => '批量采集任务已创建并开始执行',
                    'data' => [
                        'id' => $result['task_id']
                    ]
                ]);
            }
            return redirect()->route('collection-tasks.show', $result['task_id'])
                ->with('success', '批量采集任务已创建并开始执行');
        } else {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 400);
            }
            return redirect()->back()
                ->withErrors(['error' => $result['message']])
                ->withInput();
        }
    }

    /**
     * 查看任务详情
     *
     * @param int $id 任务ID
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $task = CollectionTask::findOrFail($id);
            $task->load(['taskDetails.server', 'taskDetails.collector', 'creator']);
            
            // 按服务器分组任务详情
            $detailsByServer = $task->taskDetails->groupBy('server_id');
            
            // 统计信息
            $stats = [
                'total' => $task->taskDetails->count(),
                'pending' => $task->taskDetails->where('status', 0)->count(),
                'running' => $task->taskDetails->where('status', 1)->count(),
                'completed' => $task->taskDetails->where('status', 2)->count(),
                'failed' => $task->taskDetails->where('status', 3)->count()
            ];
            
            // 如果是AJAX请求，只返回任务详情数据
            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'detailsByServer' => $detailsByServer,
                        'stats' => $stats
                    ]
                ]);
            }
            
            return view('collection-tasks.show', compact('task', 'detailsByServer', 'stats'));
        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => '任务不存在或已被删除'
                ], 404);
            }
            return redirect()->route('collection-tasks.index')
                ->with('error', '任务不存在或已被删除');
        }
    }

    /**
     * 重新执行失败的任务项
     *
     * @param CollectionTask $task
     * @return \Illuminate\Http\JsonResponse
     */
    public function retryFailed(CollectionTask $task)
    {
        if (!$task->canRetry()) {
            return response()->json([
                'success' => false,
                'message' => '该任务不能重试'
            ]);
        }

        $result = $this->collectionService->retryFailedTasks($task);
        
        return response()->json($result);
    }

    /**
     * 获取任务进度（AJAX接口）
     *
     * @param CollectionTask $task
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProgress(CollectionTask $task)
    {
        $progress = $this->collectionService->getTaskProgress($task);
        
        return response()->json([
            'success' => true,
            'data' => $progress
        ]);
    }

    /**
     * 获取任务详情结果（AJAX接口）
     *
     * @param TaskDetail $detail
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTaskDetailResult(TaskDetail $detail)
    {
        if (!$detail->hasResult()) {
            return response()->json([
                'success' => false,
                'message' => '该任务详情没有结果数据'
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $detail->id,
                'server_name' => $detail->server->name,
                'collector_name' => $detail->collector->name,
                'status' => $detail->status,
                'status_text' => $detail->statusText,
                'result' => $detail->result,
                'error_message' => $detail->error_message,
                'execution_time' => $detail->execution_time,
                'completed_at' => $detail->completed_at ? $detail->completed_at->format('Y-m-d H:i:s') : null
            ]
        ]);
    }

    /**
     * 删除任务
     *
     * @param CollectionTask $task
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $task = \App\Models\CollectionTask::findOrFail($id);
            
            if ($task->isRunning()) {
                return redirect()->back()
                    ->with('error', '正在执行的任务不能删除');
            }

            $task->delete();

            return redirect()->route('collection-tasks.index')
                ->with('success', '任务已成功删除');
        } catch (\Exception $e) {
            return redirect()->route('collection-tasks.index')
                ->with('error', '删除任务失败：' . $e->getMessage());
        }
    }
    
    /**
     * 手动触发批量任务执行
     *
     * @param int $id 任务ID
     * @return \Illuminate\Http\Response
     */
    public function triggerBatchTask($id)
    {
        try {
            $task = \App\Models\CollectionTask::findOrFail($id);
            
            // 检查是否为批量任务且未执行
            if ($task->type === 'single' || $task->status !== 0) {
                return response()->json([
                    'success' => false,
                    'message' => '只能触发未执行的批量任务'
                ]);
            }
            
            // 更新任务状态为进行中
            $task->status = 1;
            $task->started_at = now();
            $task->save();
            
            // 获取该批量任务下的所有子任务
            $taskDetails = $task->taskDetails;
            
            // 更新所有子任务状态为进行中
            foreach ($taskDetails as $detail) {
                if ($detail->status === 0) { // 只处理未开始的子任务
                    $detail->status = 1; // 设置为进行中
                    $detail->started_at = now();
                    $detail->save();
                }
            }
            
            // 直接执行批量任务执行逻辑（不使用队列）
            $job = new \App\Jobs\ExecuteBatchCollectionJob($task->id);
            $job->handle($task->id);
            
            // 如果是AJAX请求，返回JSON响应
            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => '批量任务已触发，所有子任务开始执行'
                ]);
            }
            
            // 否则重定向到任务详情页
            return redirect()->route('collection-tasks.show', $task->id)
                ->with('success', '批量任务已手动触发，所有子任务开始执行');
        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => '触发任务失败：' . $e->getMessage()
                ]);
            }
            
            return redirect()->route('collection-tasks.index')
                ->with('error', '触发任务失败：' . $e->getMessage());
        }
    }

    /**
     * 取消正在执行的任务
     *
     * @param CollectionTask $task
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancel(CollectionTask $task)
    {
        if (!$task->isRunning()) {
            return response()->json([
                'success' => false,
                'message' => '只能取消正在执行的任务'
            ]);
        }

        // 更新任务状态为失败
        $task->update([
            'status' => 3, // 失败
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

        return response()->json([
            'success' => true,
            'message' => '任务已取消'
        ]);
    }
}