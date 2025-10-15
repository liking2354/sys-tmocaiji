<?php

namespace App\Http\Controllers;

use App\Models\SystemChangeTask;
use App\Models\ConfigTemplate;
use App\Models\ServerGroup;
use App\Models\Server;
use App\Services\SystemChangeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SystemChangeTaskController extends Controller
{
    protected $systemChangeService;

    public function __construct(SystemChangeService $systemChangeService)
    {
        $this->systemChangeService = $systemChangeService;
    }

    /**
     * 显示变更任务列表
     */
    public function index(Request $request)
    {
        $query = SystemChangeTask::with(['serverGroup']);
        
        // 搜索功能
        if ($request->filled('search')) {
            $query->search($request->search);
        }
        
        // 状态筛选
        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }
        
        // 服务器分组筛选
        if ($request->filled('server_group_id')) {
            $query->byServerGroup($request->server_group_id);
        }
        
        // 时间范围筛选
        if ($request->filled('date_range')) {
            switch ($request->date_range) {
                case 'today':
                    $query->whereDate('created_at', today());
                    break;
                case 'week':
                    $query->where('created_at', '>=', now()->subWeek());
                    break;
                case 'month':
                    $query->where('created_at', '>=', now()->subMonth());
                    break;
            }
        }
        
        $tasks = $query->orderBy('created_at', 'desc')->paginate(15);
        
        // 获取服务器分组列表（用于筛选）
        $serverGroups = ServerGroup::orderBy('name')->get();
        
        return view('system-change.tasks.index', compact('tasks', 'serverGroups'));
    }

    /**
     * 显示创建变更任务表单
     */
    public function create(Request $request)
    {
        $serverGroups = ServerGroup::withCount('servers')->with('servers')->orderBy('name')->get();
        $templates = ConfigTemplate::active()->orderBy('name')->get();
        
        // 如果从服务器分组页面跳转过来，预选分组
        $selectedServerGroupId = $request->get('server_group_id');
        $selectedServerGroupName = $request->get('server_group_name');
        
        // 生成默认任务名称
        $defaultTaskName = '';
        if ($selectedServerGroupId && $selectedServerGroupName) {
            $today = now()->format('Y-m-d');
            // 查询今天该分组已创建的任务数量，用于生成批次号
            $todayTaskCount = SystemChangeTask::where('server_group_id', $selectedServerGroupId)
                ->whereDate('created_at', $today)
                ->count();
            $batchNumber = str_pad($todayTaskCount + 1, 2, '0', STR_PAD_LEFT);
            $defaultTaskName = "{$selectedServerGroupName}-{$today}-{$batchNumber}";
        }
        
        return view('system-change.tasks.create', compact('serverGroups', 'templates', 'selectedServerGroupId', 'selectedServerGroupName', 'defaultTaskName'));
    }

    /**
     * 存储新的变更任务
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'server_group_id' => 'required|exists:server_groups,id',
            'server_ids' => 'required|array|min:1',
            'server_ids.*' => 'exists:servers,id',
            'template_ids' => 'required|array|min:1',
            'template_ids.*' => 'exists:config_templates,id',
            'execution_order' => 'required|in:sequential,parallel',
            'config_variables' => 'nullable|array',
            'scheduled_at' => 'nullable|date|after:now'
        ]);
        
        DB::beginTransaction();
        
        try {
            $task = SystemChangeTask::create([
                'name' => $request->name,
                'description' => $request->description,
                'server_group_id' => $request->server_group_id,
                'server_ids' => $request->server_ids,
                'template_ids' => $request->template_ids,
                'config_variables' => $request->config_variables,
                'execution_order' => $request->execution_order,
                'total_servers' => count($request->server_ids),
                'scheduled_at' => $request->scheduled_at,
                'status' => $request->scheduled_at ? SystemChangeTask::STATUS_PENDING : SystemChangeTask::STATUS_DRAFT,
                'created_by' => auth()->user()->name ?? 'system'
            ]);
            
            // 创建任务详情
            $this->systemChangeService->createTaskDetails($task);
            
            DB::commit();
            
            return redirect()->route('system-change.tasks.show', $task)
                ->with('success', '变更任务创建成功');
                
        } catch (\Exception $e) {
            DB::rollback();
            
            return redirect()->back()
                ->withInput()
                ->with('error', '创建任务失败: ' . $e->getMessage());
        }
    }

    /**
     * 显示变更任务详情
     */
    public function show(SystemChangeTask $task)
    {
        $task->load([
            'serverGroup',
            'taskDetails' => function ($query) {
                $query->with(['server'])->orderBy('created_at');
            }
        ]);
        
        // 获取选中的模板和服务器
        $templates = $task->selectedTemplates;
        $servers = $task->selectedServers;
        
        // 获取执行统计
        $statistics = [
            'total' => $task->taskDetails->count(),
            'pending' => $task->taskDetails->where('status', 'pending')->count(),
            'running' => $task->taskDetails->where('status', 'running')->count(),
            'completed' => $task->taskDetails->where('status', 'completed')->count(),
            'failed' => $task->taskDetails->where('status', 'failed')->count(),
            'skipped' => $task->taskDetails->where('status', 'skipped')->count(),
        ];
        
        return view('system-change.tasks.show', compact('task', 'templates', 'servers', 'statistics'));
    }

    /**
     * 显示编辑变更任务表单
     */
    public function edit(SystemChangeTask $task)
    {
        // 只有草稿状态的任务才能编辑
        if ($task->status !== SystemChangeTask::STATUS_DRAFT) {
            return redirect()->back()
                ->with('error', '只有草稿状态的任务才能编辑');
        }
        
        $serverGroups = ServerGroup::with('servers')->orderBy('name')->get();
        $templates = ConfigTemplate::active()->orderBy('name')->get();
        
        return view('system-change.tasks.edit', compact('task', 'serverGroups', 'templates'));
    }

    /**
     * 更新变更任务
     */
    public function update(Request $request, SystemChangeTask $task)
    {
        // 只有草稿状态的任务才能编辑
        if ($task->status !== SystemChangeTask::STATUS_DRAFT) {
            return redirect()->back()
                ->with('error', '只有草稿状态的任务才能编辑');
        }
        
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'server_group_id' => 'required|exists:server_groups,id',
            'server_ids' => 'required|array|min:1',
            'server_ids.*' => 'exists:servers,id',
            'template_ids' => 'required|array|min:1',
            'template_ids.*' => 'exists:config_templates,id',
            'execution_order' => 'required|in:sequential,parallel',
            'config_variables' => 'nullable|array',
            'scheduled_at' => 'nullable|date|after:now'
        ]);
        
        DB::beginTransaction();
        
        try {
            $task->update([
                'name' => $request->name,
                'description' => $request->description,
                'server_group_id' => $request->server_group_id,
                'server_ids' => $request->server_ids,
                'template_ids' => $request->template_ids,
                'config_variables' => $request->config_variables,
                'execution_order' => $request->execution_order,
                'total_servers' => count($request->server_ids),
                'scheduled_at' => $request->scheduled_at,
                'status' => $request->scheduled_at ? SystemChangeTask::STATUS_PENDING : SystemChangeTask::STATUS_DRAFT,
            ]);
            
            // 重新创建任务详情
            $task->taskDetails()->delete();
            $this->systemChangeService->createTaskDetails($task);
            
            DB::commit();
            
            return redirect()->route('system-change.tasks.show', $task)
                ->with('success', '变更任务更新成功');
                
        } catch (\Exception $e) {
            DB::rollback();
            
            return redirect()->back()
                ->withInput()
                ->with('error', '更新任务失败: ' . $e->getMessage());
        }
    }

    /**
     * 删除变更任务
     */
    public function destroy(SystemChangeTask $task)
    {
        // 只有草稿、已完成、失败、已取消的任务才能删除
        if (!in_array($task->status, [
            SystemChangeTask::STATUS_DRAFT,
            SystemChangeTask::STATUS_COMPLETED,
            SystemChangeTask::STATUS_FAILED,
            SystemChangeTask::STATUS_CANCELLED
        ])) {
            return redirect()->back()
                ->with('error', '该任务当前状态不允许删除');
        }
        
        $task->delete();
        
        return redirect()->route('system-change.tasks.index')
            ->with('success', '变更任务删除成功');
    }

    /**
     * 执行变更任务
     */
    public function execute(SystemChangeTask $task)
    {
        // 先检查任务是否可以执行（在修改状态之前）
        if (!$task->canExecute()) {
            return response()->json([
                'success' => false,
                'message' => '该任务当前状态不允许执行'
            ]);
        }
        
        try {
            Log::info("开始执行任务: {$task->name} (ID: {$task->id})");
            
            // 直接同步执行任务（让服务自己管理状态）
            $this->systemChangeService->executeTask($task);
            
            // 刷新任务数据
            $task->refresh();
            
            Log::info("任务执行完成: {$task->name} (ID: {$task->id}), 状态: {$task->status}");
            
            return response()->json([
                'success' => true,
                'message' => '任务执行完成',
                'task' => [
                    'id' => $task->id,
                    'status' => $task->status,
                    'progress' => $task->progress,
                    'completed_servers' => $task->completed_servers,
                    'failed_servers' => $task->failed_servers,
                    'total_servers' => $task->total_servers,
                    'started_at' => $task->started_at ? $task->started_at->format('Y-m-d H:i:s') : null,
                    'completed_at' => $task->completed_at ? $task->completed_at->format('Y-m-d H:i:s') : null
                ]
            ]);
                
        } catch (\Exception $e) {
            Log::error("执行任务失败: {$e->getMessage()}", [
                'task_id' => $task->id,
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            
            // 更新任务状态为失败
            $task->update([
                'status' => SystemChangeTask::STATUS_FAILED,
                'completed_at' => now()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '执行任务失败: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * 暂停变更任务
     */
    public function pause(SystemChangeTask $task)
    {
        if (!$task->canPause()) {
            return response()->json([
                'success' => false,
                'message' => '该任务当前状态不允许暂停'
            ]);
        }
        
        $task->update(['status' => SystemChangeTask::STATUS_PAUSED]);
        
        return response()->json([
            'success' => true,
            'message' => '任务已暂停'
        ]);
    }

    /**
     * 取消变更任务
     */
    public function cancel(SystemChangeTask $task)
    {
        if (!$task->canCancel()) {
            return response()->json([
                'success' => false,
                'message' => '该任务当前状态不允许取消'
            ]);
        }
        
        $task->update([
            'status' => SystemChangeTask::STATUS_CANCELLED,
            'completed_at' => now()
        ]);
        
        // 取消所有未执行的详情
        $task->taskDetails()
            ->where('status', 'pending')
            ->update(['status' => 'skipped']);
        
        return response()->json([
            'success' => true,
            'message' => '任务已取消'
        ]);
    }

    /**
     * 复制变更任务
     */
    public function duplicate(SystemChangeTask $task)
    {
        try {
            $newTask = $task->replicate();
            $newTask->name = $task->name . ' (副本)';
            $newTask->status = SystemChangeTask::STATUS_DRAFT;
            $newTask->progress = 0;
            $newTask->completed_servers = 0;
            $newTask->failed_servers = 0;
            $newTask->scheduled_at = null;
            $newTask->started_at = null;
            $newTask->completed_at = null;
            $newTask->created_by = auth()->user()->name ?? 'system';
            $newTask->save();
            
            // 复制任务详情
            foreach ($task->taskDetails as $detail) {
                $newDetail = $detail->replicate();
                $newDetail->task_id = $newTask->id;
                $newDetail->status = 'pending';
                $newDetail->original_content = null;
                $newDetail->new_content = null;
                $newDetail->error_message = null;
                $newDetail->execution_log = null;
                $newDetail->backup_created = false;
                $newDetail->backup_path = null;
                $newDetail->started_at = null;
                $newDetail->completed_at = null;
                $newDetail->save();
            }
            
            return response()->json([
                'success' => true,
                'message' => '任务复制成功',
                'task_id' => $newTask->id
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '复制任务失败: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * 获取服务器分组的服务器列表
     */
    public function getServers(Request $request, $serverGroupId = null)
    {
        $groupIds = $request->input('group_ids', []);
        
        if (empty($groupIds) && $serverGroupId) {
            $groupIds = [$serverGroupId];
        }
        
        $servers = Server::whereIn('group_id', $groupIds)
            ->select('id', 'name', 'ip', 'port', 'status')
            ->orderBy('name')
            ->get();
        
        return response()->json(['servers' => $servers]);
    }

    /**
     * 获取模板变量
     */
    public function getTemplateVariables(Request $request)
    {
        try {
            $templateIds = $request->input('template_ids', []);
            
            if (empty($templateIds)) {
                return response()->json([
                    'success' => true,
                    'variables' => []
                ]);
            }
            
            $variables = [];
            $templates = ConfigTemplate::whereIn('id', $templateIds)->get();
            
            foreach ($templates as $template) {
                // 检查新的可视化配置格式 - 支持多变量
                if ($template->config_rules) {
                    $rules = is_string($template->config_rules) 
                        ? json_decode($template->config_rules, true) 
                        : $template->config_rules;
                    
                    if (is_array($rules)) {
                        foreach ($rules as $rule) {
                            // 新的多变量格式
                            if (isset($rule['variables']) && is_array($rule['variables'])) {
                                foreach ($rule['variables'] as $varConfig) {
                                    if (isset($varConfig['variable']) && !empty($varConfig['variable'])) {
                                        $variables[$varConfig['variable']] = [
                                            'description' => $rule['description'] ?? '',
                                            'default_value' => '',
                                            'required' => true,
                                            'template' => $template->name,
                                            'match_type' => $varConfig['match_type'] ?? 'key_value'
                                        ];
                                    }
                                }
                            }
                            // 兼容旧的单变量格式
                            elseif (isset($rule['variable']) && !empty($rule['variable'])) {
                                $variables[$rule['variable']] = [
                                    'description' => $rule['description'] ?? '',
                                    'default_value' => '',
                                    'required' => true,
                                    'template' => $template->name,
                                    'match_type' => $rule['match_type'] ?? 'key_value'
                                ];
                            }
                            // 检查replacement_rules格式
                            elseif (isset($rule['variable_name']) && !empty($rule['variable_name'])) {
                                $variables[$rule['variable_name']] = [
                                    'description' => $rule['description'] ?? '',
                                    'default_value' => $rule['default_value'] ?? '',
                                    'required' => $rule['required'] ?? false,
                                    'template' => $template->name
                                ];
                            }
                        }
                    }
                }
                
                // 检查旧的template_variables格式
                if ($template->template_variables) {
                    $templateVars = is_string($template->template_variables) 
                        ? json_decode($template->template_variables, true) 
                        : $template->template_variables;
                    
                    if (is_array($templateVars)) {
                        foreach ($templateVars as $var) {
                            if (isset($var['name']) && !empty($var['name'])) {
                                $variables[$var['name']] = [
                                    'description' => $var['description'] ?? '',
                                    'default_value' => $var['default_value'] ?? '',
                                    'required' => $var['required'] ?? false,
                                    'template' => $template->name
                                ];
                            }
                        }
                    }
                }
                
                // 检查旧的config_items格式
                if ($template->config_items) {
                    $configItems = is_string($template->config_items) 
                        ? json_decode($template->config_items, true) 
                        : $template->config_items;
                    
                    if (is_array($configItems)) {
                        foreach ($configItems as $item) {
                            if (isset($item['key']) && !empty($item['key'])) {
                                $variables[$item['key']] = [
                                    'description' => $item['description'] ?? '',
                                    'default_value' => $item['default_value'] ?? '',
                                    'required' => true,
                                    'template' => $template->name
                                ];
                            }
                        }
                    }
                }
            }
            
            return response()->json([
                'success' => true,
                'variables' => $variables
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '获取模板变量失败: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 获取任务执行状态
     */
    public function getStatus(SystemChangeTask $task)
    {
        try {
            $task->load(['taskDetails.server', 'taskDetails.template']);
            
            return response()->json([
                'success' => true,
                'task' => [
                    'id' => $task->id,
                    'name' => $task->name,
                    'status' => $task->status,
                    'progress' => $task->progress ?? 0,
                    'completed_servers' => $task->completed_servers ?? 0,
                    'failed_servers' => $task->failed_servers ?? 0,
                    'total_servers' => $task->total_servers ?? 0,
                    'started_at' => $task->started_at ? $task->started_at->format('Y-m-d H:i:s') : null,
                    'completed_at' => $task->completed_at ? $task->completed_at->format('Y-m-d H:i:s') : null,
                    'execution_log' => $task->execution_log
                ],
                'details' => $task->taskDetails->map(function ($detail) {
                    return [
                        'id' => $detail->id,
                        'server_id' => $detail->server_id,
                        'server_name' => $detail->server ? $detail->server->name : ($detail->server_name ?? '未知服务器'),
                        'server_ip' => $detail->server ? $detail->server->ip : ($detail->server_ip ?? '未知IP'),
                        'template_id' => $detail->template_id,
                        'template_name' => $detail->template ? $detail->template->name : '未知模板',
                        'status' => $detail->status,
                        'error_message' => $detail->error_message,
                        'result' => $detail->result,
                        'executed_at' => $detail->executed_at ? $detail->executed_at->format('Y-m-d H:i:s') : null,
                        'target_path' => $detail->target_path,
                        'config_variables' => $detail->config_variables
                    ];
                })
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '获取任务状态失败: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 批量删除任务
     */
    public function batchDelete(Request $request)
    {
        try {
            $request->validate([
                'task_ids' => 'required|array',
                'task_ids.*' => 'integer|exists:system_change_tasks,id'
            ]);

            $taskIds = $request->task_ids;
            
            // 检查任务状态，不能删除正在执行中的任务
            $tasks = SystemChangeTask::whereIn('id', $taskIds)->get();
            $cannotDeleteTasks = $tasks->whereIn('status', ['running']);
            
            if ($cannotDeleteTasks->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => '不能删除正在执行中的任务，有 ' . $cannotDeleteTasks->count() . ' 个任务无法删除'
                ], 400);
            }

            // 删除任务详情
            DB::transaction(function () use ($taskIds) {
                // 删除任务详情
                \App\Models\SystemChangeTaskDetail::whereIn('task_id', $taskIds)->delete();
                
                // 删除任务
                SystemChangeTask::whereIn('id', $taskIds)->delete();
            });

            return response()->json([
                'success' => true,
                'message' => '批量删除成功',
                'deleted_count' => count($taskIds)
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => '参数验证失败: ' . implode(', ', $e->validator->errors()->all())
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '批量删除失败: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 还原单个任务详情
     */
    public function revertTaskDetail(Request $request, $detailId)
    {
        try {
            $detail = \App\Models\SystemChangeTaskDetail::findOrFail($detailId);
            
            if (!$detail->canRevert()) {
                return response()->json([
                    'success' => false,
                    'message' => '该任务详情不能还原'
                ]);
            }

            $revertedBy = auth()->user()->name ?? 'system';
            $result = $this->systemChangeService->revertTaskDetail($detail, $revertedBy);

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '还原失败: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 批量还原任务详情
     */
    public function batchRevertTaskDetails(Request $request)
    {
        try {
            $request->validate([
                'detail_ids' => 'required|array',
                'detail_ids.*' => 'integer|exists:system_change_task_details,id'
            ]);

            $detailIds = $request->detail_ids;
            $revertedBy = auth()->user()->name ?? 'system';
            
            $results = $this->systemChangeService->batchRevertTaskDetails($detailIds, $revertedBy);

            $message = "批量还原完成：成功 {$results['success']} 个，失败 {$results['failed']} 个";
            
            if ($results['failed'] > 0) {
                $message .= "。失败原因：" . implode('；', $results['errors']);
            }

            return response()->json([
                'success' => $results['failed'] === 0,
                'message' => $message,
                'results' => $results
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => '参数验证失败: ' . implode(', ', $e->validator->errors()->all())
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '批量还原失败: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 还原整个任务的所有变更
     */
    public function revertTask(SystemChangeTask $task)
    {
        try {
            // 获取所有可还原的任务详情
            $revertableDetails = $task->taskDetails()->canRevert()->get();
            
            if ($revertableDetails->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => '该任务没有可还原的变更'
                ]);
            }

            $detailIds = $revertableDetails->pluck('id')->toArray();
            $revertedBy = auth()->user()->name ?? 'system';
            
            $results = $this->systemChangeService->batchRevertTaskDetails($detailIds, $revertedBy);

            $message = "任务还原完成：成功 {$results['success']} 个，失败 {$results['failed']} 个";
            
            if ($results['failed'] > 0) {
                $message .= "。失败原因：" . implode('；', $results['errors']);
            }

            return response()->json([
                'success' => $results['failed'] === 0,
                'message' => $message,
                'results' => $results
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '任务还原失败: ' . $e->getMessage()
            ], 500);
        }
    }
}