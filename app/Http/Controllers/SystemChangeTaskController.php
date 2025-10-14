<?php

namespace App\Http\Controllers;

use App\Models\SystemChangeTask;
use App\Models\ConfigTemplate;
use App\Models\ServerGroup;
use App\Models\Server;
use App\Services\SystemChangeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        $serverGroups = ServerGroup::with('servers')->orderBy('name')->get();
        $templates = ConfigTemplate::active()->orderBy('name')->get();
        
        // 如果从服务器分组页面跳转过来，预选分组
        $selectedServerGroupId = $request->get('server_group_id');
        
        return view('system-change.tasks.create', compact('serverGroups', 'templates', 'selectedServerGroupId'));
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
                $query->with(['server', 'template'])->orderByExecution();
            }
        ]);
        
        // 获取执行统计
        $statistics = [
            'total' => $task->taskDetails->count(),
            'pending' => $task->taskDetails->where('status', 'pending')->count(),
            'running' => $task->taskDetails->where('status', 'running')->count(),
            'completed' => $task->taskDetails->where('status', 'completed')->count(),
            'failed' => $task->taskDetails->where('status', 'failed')->count(),
            'skipped' => $task->taskDetails->where('status', 'skipped')->count(),
        ];
        
        return view('system-change.tasks.show', compact('task', 'statistics'));
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
        if (!$task->canExecute()) {
            return redirect()->back()
                ->with('error', '该任务当前状态不允许执行');
        }
        
        try {
            $this->systemChangeService->executeTask($task);
            
            return redirect()->back()
                ->with('success', '任务已开始执行');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', '执行任务失败: ' . $e->getMessage());
        }
    }

    /**
     * 暂停变更任务
     */
    public function pause(SystemChangeTask $task)
    {
        if (!$task->canPause()) {
            return redirect()->back()
                ->with('error', '该任务当前状态不允许暂停');
        }
        
        $task->update(['status' => SystemChangeTask::STATUS_PAUSED]);
        
        return redirect()->back()
            ->with('success', '任务已暂停');
    }

    /**
     * 取消变更任务
     */
    public function cancel(SystemChangeTask $task)
    {
        if (!$task->canCancel()) {
            return redirect()->back()
                ->with('error', '该任务当前状态不允许取消');
        }
        
        $task->update([
            'status' => SystemChangeTask::STATUS_CANCELLED,
            'completed_at' => now()
        ]);
        
        // 取消所有未执行的详情
        $task->taskDetails()
            ->where('status', 'pending')
            ->update(['status' => 'skipped']);
        
        return redirect()->back()
            ->with('success', '任务已取消');
    }

    /**
     * 复制变更任务
     */
    public function duplicate(SystemChangeTask $task)
    {
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
        
        return redirect()->route('system-change.tasks.edit', $newTask)
            ->with('success', '任务复制成功');
    }

    /**
     * 获取服务器分组的服务器列表
     */
    public function getServers(Request $request, $serverGroupId)
    {
        $servers = Server::where('server_group_id', $serverGroupId)
            ->select('id', 'name', 'hostname', 'status')
            ->get();
        
        return response()->json($servers);
    }

    /**
     * 获取模板变量
     */
    public function getTemplateVariables(Request $request)
    {
        $templateIds = $request->input('template_ids', []);
        
        $variables = [];
        $templates = ConfigTemplate::whereIn('id', $templateIds)->get();
        
        foreach ($templates as $template) {
            $templateVariables = $template->variables;
            $variables = array_merge($variables, $templateVariables);
        }
        
        return response()->json([
            'variables' => array_unique($variables)
        ]);
    }

    /**
     * 获取任务执行状态
     */
    public function getStatus(SystemChangeTask $task)
    {
        $task->load('taskDetails');
        
        return response()->json([
            'status' => $task->status,
            'progress' => $task->progress,
            'completed_servers' => $task->completed_servers,
            'failed_servers' => $task->failed_servers,
            'total_servers' => $task->total_servers,
            'details' => $task->taskDetails->map(function ($detail) {
                return [
                    'id' => $detail->id,
                    'server_name' => $detail->server->name,
                    'template_name' => $detail->template->name,
                    'status' => $detail->status,
                    'error_message' => $detail->error_message
                ];
            })
        ]);
    }
}