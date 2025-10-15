@extends('layouts.app')

@section('title', '配置任务详情')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">配置任务详情</h3>
                    <div class="btn-group">
                        <a href="{{ route('system-change.tasks.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> 返回列表
                        </a>
                        @if(in_array($task->status, ['draft', 'pending']))
                        <button type="button" class="btn btn-success" onclick="executeTaskWithProgress({{ $task->id }})">
                            <i class="fas fa-play"></i> 执行任务
                        </button>
                        @endif
                        @if($task->status === 'failed')
                        <button type="button" class="btn btn-warning" onclick="executeTaskWithProgress({{ $task->id }})">
                            <i class="fas fa-redo"></i> 重新执行
                        </button>
                        @endif
                        @if(in_array($task->status, ['draft', 'pending', 'failed']))
                        <a href="{{ route('system-change.tasks.edit', $task->id) }}" class="btn btn-primary">
                            <i class="fas fa-edit"></i> 编辑
                        </a>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <!-- 基本信息 -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>基本信息</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <td width="120"><strong>任务名称:</strong></td>
                                    <td>{{ $task->name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>描述:</strong></td>
                                    <td>{{ $task->description ?? '无' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>状态:</strong></td>
                                    <td>{!! $task->getStatusBadge() !!}</td>
                                </tr>
                                <tr>
                                    <td><strong>服务器分组:</strong></td>
                                    <td>
                                        @if($task->serverGroup)
                                            <span class="badge badge-info">{{ $task->serverGroup->name }}</span>
                                        @else
                                            <span class="text-muted">自定义选择</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>创建时间:</strong></td>
                                    <td>{{ $task->created_at->format('Y-m-d H:i:s') }}</td>
                                </tr>
                                @if($task->executed_at)
                                <tr>
                                    <td><strong>执行时间:</strong></td>
                                    <td>{{ $task->executed_at->format('Y-m-d H:i:s') }}</td>
                                </tr>
                                @endif
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5>模板信息</h5>
                            @if($templates->count() > 0)
                                @foreach($templates as $template)
                                <div class="mb-3">
                                    <table class="table table-sm table-bordered">
                                        <tr>
                                            <td width="80"><strong>模板名:</strong></td>
                                            <td>{{ $template->name }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>类型:</strong></td>
                                            <td>{{ $template->template_type ?? '未知' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>描述:</strong></td>
                                            <td>{{ $template->description ?? '无' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>状态:</strong></td>
                                            <td>
                                                @if($template->is_active)
                                                    <span class="badge badge-success">启用</span>
                                                @else
                                                    <span class="badge badge-secondary">禁用</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @if($template->variables)
                                        <tr>
                                            <td><strong>变量:</strong></td>
                                            <td><small>{{ implode(', ', $template->used_variables) }}</small></td>
                                        </tr>
                                        @endif
                                    </table>
                                </div>
                                @endforeach
                            @else
                                <p class="text-muted">无关联模板</p>
                            @endif
                        </div>
                    </div>

                    <!-- 服务器信息 -->
                    @if($servers->count() > 0)
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5>目标服务器 
                                <small class="text-muted">
                                    (来自分组: 
                                    @if($task->serverGroup)
                                        <span class="badge badge-info">{{ $task->serverGroup->name }}</span>
                                    @else
                                        <span class="text-muted">自定义选择</span>
                                    @endif
                                    )
                                </small>
                            </h5>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>服务器名称</th>
                                            <th>IP地址</th>
                                            <th>端口</th>
                                            <th>用户名</th>
                                            <th>状态</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($servers as $server)
                                        <tr>
                                            <td>{{ $server->name }}</td>
                                            <td><code>{{ $server->ip }}</code></td>
                                            <td>{{ $server->port }}</td>
                                            <td>{{ $server->username }}</td>
                                            <td>
                                                @if($server->status == 1)
                                                    <span class="badge badge-success">活跃</span>
                                                @else
                                                    <span class="badge badge-secondary">离线</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- 任务详情 -->
                    @if($task->taskDetails->count() > 0)
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5>执行详情</h5>
                                <div class="btn-group">
                                    @php
                                        $hasRevertableDetails = $task->taskDetails->where('status', 'completed')->where('is_reverted', false)->whereNotNull('original_content')->count() > 0;
                                    @endphp
                                    @if($hasRevertableDetails)
                                        <button type="button" class="btn btn-warning btn-sm" onclick="revertAllTask({{ $task->id }})">
                                            <i class="fas fa-undo"></i> 还原整个任务
                                        </button>
                                        <button type="button" class="btn btn-outline-warning btn-sm" onclick="batchRevertSelected()" id="batchRevertBtn" style="display: none;">
                                            <i class="fas fa-undo"></i> 批量还原选中
                                        </button>
                                    @endif
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>
                                                <input type="checkbox" id="selectAll" class="form-check-input">
                                            </th>
                                            <th>服务器</th>
                                            <th>操作类型</th>
                                            <th>目标路径</th>
                                            <th>变量配置</th>
                                            <th>状态</th>
                                            <th>还原状态</th>
                                            <th>执行结果</th>
                                            <th>操作</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($task->taskDetails as $detail)
                                        <tr>
                                            <td>
                                                @if($detail->canRevert())
                                                <input type="checkbox" name="detail_ids[]" value="{{ $detail->id }}" class="form-check-input detail-checkbox">
                                                @endif
                                            </td>
                                            <td>
                                                <strong>{{ $detail->server_name ?? '未知服务器' }}</strong><br>
                                                <small class="text-muted">{{ $detail->server_ip ?? '未知IP' }}</small>
                                            </td>
                                            <td>
                                                @switch($detail->rule_type)
                                                    @case('directory')
                                                        <span class="badge badge-info">目录规则</span>
                                                        @break
                                                    @case('file')
                                                        <span class="badge badge-primary">文件规则</span>
                                                        @break
                                                    @case('string')
                                                        <span class="badge badge-warning">字符串规则</span>
                                                        @break
                                                    @default
                                                        <span class="badge badge-secondary">{{ $detail->rule_type ?? '未知' }}</span>
                                                @endswitch
                                            </td>
                                            <td><code>{{ $detail->target_path ?? $detail->config_file_path ?? '未知路径' }}</code></td>
                                            <td>
                                                @if($detail->config_variables)
                                                    @php
                                                        $variables = is_array($detail->config_variables) ? $detail->config_variables : json_decode($detail->config_variables, true);
                                                    @endphp
                                                    @if($variables && is_array($variables))
                                                        <small>
                                                            @foreach($variables as $key => $value)
                                                                <div><strong>{{ $key }}:</strong> {{ $value }}</div>
                                                            @endforeach
                                                        </small>
                                                    @else
                                                        <span class="text-muted">无变量</span>
                                                    @endif
                                                @else
                                                    <span class="text-muted">无变量</span>
                                                @endif
                                            </td>
                                            <td>
                                                @switch($detail->status)
                                                    @case('pending')
                                                        <span class="badge badge-warning">待执行</span>
                                                        @break
                                                    @case('running')
                                                        <span class="badge badge-info">执行中</span>
                                                        @break
                                                    @case('completed')
                                                        <span class="badge badge-success">已完成</span>
                                                        @break
                                                    @case('failed')
                                                        <span class="badge badge-danger">执行失败</span>
                                                        @break
                                                    @default
                                                        <span class="badge badge-secondary">{{ $detail->status }}</span>
                                                @endswitch
                                            </td>
                                            <td>
                                                @if($detail->is_reverted)
                                                    <span class="badge badge-warning">
                                                        <i class="fas fa-undo"></i> 已还原
                                                    </span>
                                                    @if($detail->reverted_at)
                                                        <br><small class="text-muted">{{ $detail->reverted_at->format('m-d H:i') }}</small>
                                                    @endif
                                                @elseif($detail->canRevert())
                                                    <span class="badge badge-success">
                                                        <i class="fas fa-check"></i> 可还原
                                                    </span>
                                                @else
                                                    <span class="badge badge-secondary">
                                                        <i class="fas fa-times"></i> 不可还原
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($detail->execution_log)
                                                    <button type="button" class="btn btn-sm btn-outline-info" 
                                                            data-toggle="modal" 
                                                            data-target="#resultModal{{ $detail->id }}">
                                                        查看日志
                                                    </button>
                                                @elseif($detail->status === 'completed')
                                                    <span class="text-success">执行成功</span>
                                                @elseif($detail->status === 'failed')
                                                    @if($detail->error_message)
                                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                data-toggle="modal" 
                                                                data-target="#resultModal{{ $detail->id }}">
                                                            查看错误
                                                        </button>
                                                    @else
                                                        <span class="text-danger">执行失败</span>
                                                    @endif
                                                @else
                                                    <span class="text-muted">无结果</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($detail->canRevert())
                                                    <button type="button" class="btn btn-sm btn-warning" 
                                                            onclick="revertTaskDetail({{ $detail->id }})"
                                                            title="还原此变更">
                                                        <i class="fas fa-undo"></i> 还原
                                                    </button>
                                                @elseif($detail->is_reverted && $detail->revert_log)
                                                    <button type="button" class="btn btn-sm btn-outline-info" 
                                                            data-toggle="modal" 
                                                            data-target="#revertLogModal{{ $detail->id }}"
                                                            title="查看还原日志">
                                                        <i class="fas fa-history"></i> 还原日志
                                                    </button>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- 执行日志 -->
                    @if($task->execution_log)
                    <div class="row">
                        <div class="col-12">
                            <h5>执行日志</h5>
                            <div class="card">
                                <div class="card-body">
                                    <pre class="mb-0" style="max-height: 400px; overflow-y: auto;">{{ $task->execution_log }}</pre>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 执行结果模态框 -->
@if($task->taskDetails->count() > 0)
@foreach($task->taskDetails as $detail)
@if($detail->execution_log || $detail->error_message)
<div class="modal fade" id="resultModal{{ $detail->id }}" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    @if($detail->status === 'failed')
                        执行错误 - {{ $detail->server_name ?? $detail->server->name ?? '未知服务器' }}
                    @else
                        执行日志 - {{ $detail->server_name ?? $detail->server->name ?? '未知服务器' }}
                    @endif
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                @if($detail->status === 'failed' && $detail->error_message)
                    <div class="alert alert-danger">
                        <h6><i class="fas fa-exclamation-triangle"></i> 错误信息</h6>
                        <p class="mb-0">{{ $detail->error_message }}</p>
                    </div>
                @endif
                
                <!-- 标签页导航 -->
                <ul class="nav nav-tabs" id="resultTabs{{ $detail->id }}" role="tablist">
                    @if($detail->execution_log)
                    <li class="nav-item">
                        <a class="nav-link active" id="log-tab{{ $detail->id }}" data-toggle="tab" href="#log{{ $detail->id }}" role="tab">
                            <i class="fas fa-list-alt"></i> 执行日志
                        </a>
                    </li>
                    @endif
                    
                    @php
                        $changeDetails = null;
                        if ($detail->new_content) {
                            $changeDetails = json_decode($detail->new_content, true);
                        }
                    @endphp
                    
                    @if($changeDetails && is_array($changeDetails) && count($changeDetails) > 0)
                    <li class="nav-item">
                        <a class="nav-link {{ !$detail->execution_log ? 'active' : '' }}" id="changes-tab{{ $detail->id }}" data-toggle="tab" href="#changes{{ $detail->id }}" role="tab">
                            <i class="fas fa-exchange-alt"></i> 变更详情 ({{ count($changeDetails) }})
                        </a>
                    </li>
                    @endif
                </ul>
                
                <!-- 标签页内容 -->
                <div class="tab-content mt-3" id="resultTabContent{{ $detail->id }}">
                    @if($detail->execution_log)
                    <div class="tab-pane fade show active" id="log{{ $detail->id }}" role="tabpanel">
                        <pre style="max-height: 400px; overflow-y: auto; background-color: #f8f9fa; padding: 15px; border-radius: 5px; font-size: 13px;">{{ $detail->execution_log }}</pre>
                    </div>
                    @endif
                    
                    @if($changeDetails && is_array($changeDetails) && count($changeDetails) > 0)
                    <div class="tab-pane fade {{ !$detail->execution_log ? 'show active' : '' }}" id="changes{{ $detail->id }}" role="tabpanel">
                        <div class="accordion" id="changesAccordion{{ $detail->id }}">
                            @foreach($changeDetails as $index => $change)
                            <div class="card">
                                <div class="card-header" id="heading{{ $detail->id }}_{{ $index }}">
                                    <h6 class="mb-0">
                                        <button class="btn btn-link text-left" type="button" data-toggle="collapse" data-target="#collapse{{ $detail->id }}_{{ $index }}">
                                            <i class="fas fa-file-code"></i> 
                                            {{ basename($change['file'] ?? '未知文件') }}
                                            <small class="text-muted ml-2">
                                                变量: {{ $change['variable'] ?? '未知' }} | 
                                                匹配类型: {{ $change['match_type'] ?? '未知' }} |
                                                替换次数: {{ $change['replace_count'] ?? 0 }}
                                            </small>
                                        </button>
                                    </h6>
                                </div>
                                <div id="collapse{{ $detail->id }}_{{ $index }}" class="collapse" data-parent="#changesAccordion{{ $detail->id }}">
                                    <div class="card-body">
                                        <!-- 变更摘要 -->
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <strong>文件路径:</strong><br>
                                                <code>{{ $change['file'] ?? '未知' }}</code>
                                            </div>
                                            <div class="col-md-6">
                                                <strong>变更时间:</strong><br>
                                                {{ $change['timestamp'] ?? '未知' }}
                                            </div>
                                        </div>
                                        
                                        <div class="row mb-3">
                                            <div class="col-md-4">
                                                <strong>变量名:</strong><br>
                                                <span class="badge badge-info">{{ $change['variable'] ?? '未知' }}</span>
                                            </div>
                                            <div class="col-md-4">
                                                <strong>匹配类型:</strong><br>
                                                <span class="badge badge-secondary">{{ $change['match_type'] ?? '未知' }}</span>
                                            </div>
                                            <div class="col-md-4">
                                                <strong>替换次数:</strong><br>
                                                <span class="badge badge-success">{{ $change['replace_count'] ?? 0 }}</span>
                                            </div>
                                        </div>
                                        
                                        @if(isset($change['match_pattern']))
                                        <div class="mb-3">
                                            <strong>匹配模式:</strong><br>
                                            <code style="background-color: #fff3cd; padding: 2px 6px; border-radius: 3px;">{{ $change['match_pattern'] }}</code>
                                        </div>
                                        @endif
                                        
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <strong>原值:</strong><br>
                                                <code style="background-color: #f8d7da; padding: 2px 6px; border-radius: 3px;">{{ $change['old_value'] ?? '未知' }}</code>
                                            </div>
                                            <div class="col-md-6">
                                                <strong>新值:</strong><br>
                                                <code style="background-color: #d4edda; padding: 2px 6px; border-radius: 3px;">{{ $change['new_value'] ?? '未知' }}</code>
                                            </div>
                                        </div>
                                        
                                        <!-- 上下文对比 -->
                                        @if(isset($change['before_context']) || isset($change['after_context']))
                                        <div class="row">
                                            @if(isset($change['before_context']))
                                            <div class="col-md-6">
                                                <h6><i class="fas fa-minus-circle text-danger"></i> 修改前上下文</h6>
                                                <pre style="max-height: 200px; overflow-y: auto; background-color: #fff5f5; padding: 10px; border: 1px solid #fed7d7; border-radius: 3px; font-size: 12px;">{{ $change['before_context'] }}</pre>
                                            </div>
                                            @endif
                                            
                                            @if(isset($change['after_context']))
                                            <div class="col-md-6">
                                                <h6><i class="fas fa-plus-circle text-success"></i> 修改后上下文</h6>
                                                <pre style="max-height: 200px; overflow-y: auto; background-color: #f0fff4; padding: 10px; border: 1px solid #9ae6b4; border-radius: 3px; font-size: 12px;">{{ $change['after_context'] }}</pre>
                                            </div>
                                            @endif
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                    
                    @if(!$detail->execution_log && (!$changeDetails || !is_array($changeDetails) || count($changeDetails) === 0))
                    <div class="tab-pane fade show active">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> 暂无执行结果或变更记录
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endif
@endforeach
@endif

<!-- 还原日志模态框 -->
@if($task->taskDetails->count() > 0)
@foreach($task->taskDetails as $detail)
@if($detail->is_reverted && $detail->revert_log)
<div class="modal fade" id="revertLogModal{{ $detail->id }}" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-history"></i> 还原日志 - {{ $detail->server_name ?? $detail->server->name ?? '未知服务器' }}
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <h6><i class="fas fa-info-circle"></i> 还原信息</h6>
                    <p class="mb-1"><strong>还原时间:</strong> {{ $detail->reverted_at ? $detail->reverted_at->format('Y-m-d H:i:s') : '未知' }}</p>
                    <p class="mb-0"><strong>还原操作人:</strong> {{ $detail->reverted_by ?? '系统' }}</p>
                </div>
                
                <h6>还原日志</h6>
                <pre style="max-height: 400px; overflow-y: auto; background-color: #f8f9fa; padding: 15px; border-radius: 5px; font-size: 13px;">{{ $detail->revert_log }}</pre>
            </div>
        </div>
    </div>
</div>
@endif
@endforeach
@endif

<!-- 引入执行进度组件 -->
@include('components.execution-progress')
@endsection

@section('scripts')
<script>
// 确保进度管理器正确初始化
$(document).ready(function() {
    // 给一点时间让execution-progress组件加载
    setTimeout(function() {
        if (typeof initExecutionProgressManager === 'function') {
            initExecutionProgressManager();
        }
    }, 100);
});

// 执行任务并显示进度
function executeTaskWithProgress(taskId) {
    if (window.executionProgressManager) {
        // 使用进度管理器执行任务
        executeTaskWithProgressManager(taskId);
    } else {
        // 降级处理：直接执行任务
        executeTask(taskId);
    }
}

// 使用进度管理器执行任务
function executeTaskWithProgressManager(taskId) {
    const steps = [
        '准备执行环境',
        '连接目标服务器',
        '备份原始文件',
        '执行配置变更',
        '验证变更结果',
        '完成任务执行'
    ];
    
    // 初始化进度管理器
    window.executionProgressManager.init('执行系统变更任务', steps, () => executeTaskWithProgressManager(taskId));
    
    // 开始执行任务
    executeSystemChangeTask(taskId);
}

// 执行系统变更任务的具体逻辑
function executeSystemChangeTask(taskId) {
    const progressManager = window.executionProgressManager;
    if (!progressManager) {
        executeTask(taskId);
        return;
    }
    
    // 步骤1: 准备执行环境
    progressManager.startStep(0, '正在准备执行环境...');
    
    setTimeout(() => {
        progressManager.completeStep(0, true, '执行环境准备完成');
        
        // 步骤2: 开始执行任务
        progressManager.startStep(1, '正在执行任务...');
        
        $.ajax({
            url: '/system-change/tasks/' + taskId + '/execute',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    // 模拟完成所有步骤
                    for (let i = 1; i < 6; i++) {
                        progressManager.completeStep(i, true);
                    }
                    
                    progressManager.showResult(true, '任务执行成功', '系统变更任务已成功执行完成！');
                    
                    // 3秒后自动刷新页面
                    setTimeout(() => {
                        location.reload();
                    }, 3000);
                } else {
                    progressManager.completeStep(1, false, response.message || '任务执行失败');
                    progressManager.showResult(false, '任务执行失败', response.message || '未知错误');
                }
            },
            error: function(xhr) {
                let message = '任务执行失败';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                
                progressManager.completeStep(1, false, message);
                progressManager.showResult(false, '任务执行失败', message);
            }
        });
    }, 1000);
}

// 降级执行函数
function executeTask(taskId) {
    if (confirm('确定要执行此任务吗？')) {
        $('button[onclick="executeTask(' + taskId + ')"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> 执行中...');
        
        $.ajax({
            url: '/system-change/tasks/' + taskId + '/execute',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    alert('任务执行成功！');
                    location.reload();
                } else {
                    alert('任务执行失败：' + (response.message || '未知错误'));
                    $('button[onclick="executeTask(' + taskId + ')"]').prop('disabled', false).html('<i class="fas fa-play"></i> 执行任务');
                }
            },
            error: function(xhr) {
                var message = '任务执行失败';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message += '：' + xhr.responseJSON.message;
                }
                alert(message);
                $('button[onclick="executeTask(' + taskId + ')"]').prop('disabled', false).html('<i class="fas fa-play"></i> 执行任务');
            }
        });
    }
}

// 还原单个任务详情
function revertTaskDetail(detailId) {
    if (confirm('确定要还原此变更吗？还原后将恢复到执行前的状态。')) {
        var $btn = $('button[onclick="revertTaskDetail(' + detailId + ')"]');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> 还原中...');
        
        $.ajax({
            url: '/system-change/task-details/' + detailId + '/revert',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    alert('还原成功！');
                    location.reload();
                } else {
                    alert('还原失败：' + (response.message || '未知错误'));
                    $btn.prop('disabled', false).html('<i class="fas fa-undo"></i> 还原');
                }
            },
            error: function(xhr) {
                var message = '还原失败';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message += '：' + xhr.responseJSON.message;
                }
                alert(message);
                $btn.prop('disabled', false).html('<i class="fas fa-undo"></i> 还原');
            }
        });
    }
}

// 还原整个任务
function revertAllTask(taskId) {
    if (confirm('确定要还原整个任务的所有变更吗？这将恢复所有已执行的配置到执行前的状态。')) {
        var $btn = $('button[onclick="revertAllTask(' + taskId + ')"]');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> 还原中...');
        
        $.ajax({
            url: '/system-change/tasks/' + taskId + '/revert',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    alert('任务还原成功！');
                    location.reload();
                } else {
                    alert('任务还原失败：' + (response.message || '未知错误'));
                    $btn.prop('disabled', false).html('<i class="fas fa-undo"></i> 还原整个任务');
                }
            },
            error: function(xhr) {
                var message = '任务还原失败';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message += '：' + xhr.responseJSON.message;
                }
                alert(message);
                $btn.prop('disabled', false).html('<i class="fas fa-undo"></i> 还原整个任务');
            }
        });
    }
}

// 批量还原选中的任务详情
function batchRevertSelected() {
    var selectedIds = [];
    $('.detail-checkbox:checked').each(function() {
        selectedIds.push($(this).val());
    });
    
    if (selectedIds.length === 0) {
        alert('请先选择要还原的项目');
        return;
    }
    
    if (confirm('确定要还原选中的 ' + selectedIds.length + ' 个变更吗？')) {
        var $btn = $('#batchRevertBtn');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> 批量还原中...');
        
        $.ajax({
            url: '/system-change/task-details/batch-revert',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: {
                detail_ids: selectedIds
            },
            success: function(response) {
                alert(response.message || '批量还原完成');
                location.reload();
            },
            error: function(xhr) {
                var message = '批量还原失败';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message += '：' + xhr.responseJSON.message;
                }
                alert(message);
                $btn.prop('disabled', false).html('<i class="fas fa-undo"></i> 批量还原选中');
            }
        });
    }
}

// 全选/取消全选
$(document).ready(function() {
    $('#selectAll').change(function() {
        $('.detail-checkbox').prop('checked', $(this).prop('checked'));
        toggleBatchRevertButton();
    });
    
    $('.detail-checkbox').change(function() {
        var totalCheckboxes = $('.detail-checkbox').length;
        var checkedCheckboxes = $('.detail-checkbox:checked').length;
        
        $('#selectAll').prop('checked', totalCheckboxes === checkedCheckboxes);
        toggleBatchRevertButton();
    });
    
    function toggleBatchRevertButton() {
        var checkedCount = $('.detail-checkbox:checked').length;
        if (checkedCount > 0) {
            $('#batchRevertBtn').show();
        } else {
            $('#batchRevertBtn').hide();
        }
    }
});
</script>
@endsection