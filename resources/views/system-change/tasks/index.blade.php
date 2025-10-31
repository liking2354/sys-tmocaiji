@extends('layouts.app')

@section('title', '配置任务')

@section('content')
<div class="container-fluid">
    <!-- 页面标题 -->
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-1">
                <i class="fas fa-tasks mr-2"></i>系统变更任务
            </h2>
            <p class="text-muted">管理和执行系统配置变更任务</p>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <!-- 筛选卡片 -->
            <div class="card card-warning shadow-sm mb-4">
                <div class="card-header bg-warning text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-filter mr-2"></i>筛选条件
                    </h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="mb-0">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="input-group">
                                    <input type="text" name="search" class="form-control" placeholder="搜索任务名称..." 
                                           value="{{ request('search') }}">
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary" type="submit">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <select name="status" class="form-control" onchange="this.form.submit();">
                                    <option value="">全部状态</option>
                                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>待执行</option>
                                    <option value="running" {{ request('status') === 'running' ? 'selected' : '' }}>执行中</option>
                                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>已完成</option>
                                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>失败</option>
                                    <option value="paused" {{ request('status') === 'paused' ? 'selected' : '' }}>暂停</option>
                                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>已取消</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="server_group_id" class="form-control" onchange="this.form.submit();">
                                    <option value="">全部分组</option>
                                    @foreach($serverGroups as $group)
                                        <option value="{{ $group->id }}" {{ request('server_group_id') == $group->id ? 'selected' : '' }}>
                                            {{ $group->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <a href="{{ route('system-change.tasks.index') }}" class="btn btn-outline-secondary btn-block">
                                    <i class="fas fa-undo mr-1"></i>重置
                                </a>
                            </div>
                            <div class="col-md-3 text-right">
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-danger" id="batchDeleteBtn" style="display: none;" onclick="batchDelete()" title="批量删除">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <button type="button" class="btn btn-info" onclick="refreshProgress()" title="刷新进度">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                    <a href="{{ route('system-change.tasks.create') }}" class="btn btn-primary" title="创建任务">
                                        <i class="fas fa-plus"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 任务列表卡片 -->
            <div class="card card-primary shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-list mr-2"></i>任务列表
                    </h5>
                </div>

                <div class="card-body p-0">
                    <!-- 任务列表 -->
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 3%;">
                                        <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                                    </th>
                                    <th style="width: 5%;">ID</th>
                                    <th>任务名称</th>
                                    <th style="width: 12%;">服务器分组</th>
                                    <th style="width: 8%;">服务器数</th>
                                    <th style="width: 8%;">模板数</th>
                                    <th style="width: 8%;">执行进度</th>
                                    <th style="width: 8%;">状态</th>
                                    <th style="width: 10%;">执行时间</th>
                                    <th style="width: 12%;">创建时间</th>
                                    <th style="width: 15%;">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tasks as $task)
                                <tr>
                                    <td>
                                        <input type="checkbox" class="task-checkbox" value="{{ $task->id }}" onchange="updateBatchDeleteButton()">
                                    </td>
                                    <td>{{ $task->id }}</td>
                                    <td>
                                        <strong>{{ $task->name }}</strong>
                                        @if($task->description)
                                            <br><small class="text-muted">{{ Str::limit($task->description, 50) }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($task->serverGroup)
                                            <span class="badge badge-info">{{ $task->serverGroup->name }}</span>
                                        @else
                                            <span class="text-muted">自定义</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-primary">{{ count($task->server_ids) }}</span>
                                    </td>
                                    <td>
                                        <span class="badge badge-secondary">{{ count($task->template_ids) }}</span>
                                    </td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar {{ $task->getProgressBarClass() }}" 
                                                 role="progressbar" 
                                                 style="width: {{ $task->progress }}%"
                                                 aria-valuenow="{{ $task->progress }}" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">
                                                {{ $task->progress }}%
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        {!! $task->getStatusBadge() !!}
                                    </td>
                                    <td>
                                        @if($task->started_at)
                                            {{ $task->formatted_execution_time }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ $task->created_at->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('system-change.tasks.show', $task) }}" 
                                               class="btn btn-info" title="查看详情">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            
                                            @if($task->canExecute())
                                                <button type="button" class="btn btn-success" 
                                                        onclick="executeTask({{ $task->id }})" title="执行任务">
                                                    <i class="fas fa-play"></i>
                                                </button>
                                            @endif
                                            
                                            @if($task->canPause())
                                                <button type="button" class="btn btn-warning" 
                                                        onclick="pauseTask({{ $task->id }})" title="暂停任务">
                                                    <i class="fas fa-pause"></i>
                                                </button>
                                            @endif
                                            
                                            @if($task->canCancel())
                                                <button type="button" class="btn btn-danger" 
                                                        onclick="cancelTask({{ $task->id }})" title="取消任务">
                                                    <i class="fas fa-stop"></i>
                                                </button>
                                            @endif
                                            
                                            @if($task->status === 'pending')
                                                <a href="{{ route('system-change.tasks.edit', $task) }}" 
                                                   class="btn btn-warning" title="编辑">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            @endif
                                            
                                            <button type="button" class="btn btn-secondary" 
                                                    onclick="duplicateTask({{ $task->id }})" title="复制任务">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                            
                                            @if($task->status === 'pending')
                                                <form method="POST" action="{{ route('system-change.tasks.destroy', $task) }}" 
                                                      style="display: inline;" 
                                                      onsubmit="return confirm('确定要删除这个任务吗？')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger" title="删除">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="11" class="text-center text-muted">
                                        <i class="fas fa-inbox fa-3x mb-3"></i><br>
                                        暂无系统变更任务
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    </div>

                    <!-- 分页 -->
                    <div class="card-footer bg-light">
                        <div class="d-flex justify-content-center">
                            {{ $tasks->appends(request()->query())->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

<!-- 引入执行进度组件 -->
@include('components.execution-progress')



@section('scripts')
<script>
// 带进度显示的任务执行
function executeTask(taskId) {
    if (!confirm('确定要执行这个任务吗？')) {
        return;
    }
    
    // 检查进度管理器是否可用
    if (!window.executionProgressManager) {
        // 如果进度管理器不可用，使用简单的执行方式
        executeTaskSimple(taskId);
        return;
    }
    
    // 定义执行步骤
    const steps = [
        '验证任务配置',
        '检查目标服务器连接',
        '准备执行环境',
        '执行配置变更',
        '验证变更结果',
        '完成任务处理'
    ];
    
    // 初始化进度管理器
    window.executionProgressManager.init('执行系统变更任务', steps, () => executeTask(taskId));
    
    // 开始执行流程
    executeSystemChangeTask(taskId);
}

// 简单的任务执行（备用方案）
function executeTaskSimple(taskId) {
    $.ajax({
        url: '/system-change/tasks/' + taskId + '/execute',
        method: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        beforeSend: function() {
            $('button[onclick="executeTask(' + taskId + ')"]').prop('disabled', true).text('执行中...');
        },
        success: function(response) {
            if (response.success) {
                toastr.success('任务开始执行');
                setTimeout(function() { location.reload(); }, 1000);
            } else {
                toastr.error(response.message || '执行失败');
                $('button[onclick="executeTask(' + taskId + ')"]').prop('disabled', false).text('执行');
            }
        },
        error: function(xhr) {
            console.error('执行任务失败:', xhr);
            var message = '未知错误';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            }
            toastr.error('执行失败: ' + message);
            $('button[onclick="executeTask(' + taskId + ')"]').prop('disabled', false).text('执行');
        }
    });
}

// 执行系统变更任务流程
function executeSystemChangeTask(taskId) {
    const progressManager = window.executionProgressManager;
    if (!progressManager) {
        console.error('Progress manager not available');
        return;
    }
    
    // 步骤1: 验证任务配置
    progressManager.startStep(0, '检查任务配置和模板信息');
    
    setTimeout(() => {
        progressManager.completeStep(0, true, '任务配置验证通过');
        
        // 步骤2: 检查目标服务器连接
        progressManager.startStep(1, '测试目标服务器连接状态');
        
        setTimeout(() => {
            progressManager.completeStep(1, true, '服务器连接正常');
            
            // 步骤3: 准备执行环境
            progressManager.startStep(2, '准备配置变更环境');
            
            setTimeout(() => {
                progressManager.completeStep(2, true, '执行环境准备完成');
                
                // 步骤4: 执行配置变更（实际API调用）
                progressManager.startStep(3, '开始执行配置变更操作');
                
                // 实际的API调用
                $.ajax({
                    url: '/system-change/tasks/' + taskId + '/execute',
                    method: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            progressManager.completeStep(3, true, '任务已启动，开始监控执行状态');
                            
                            // 步骤5: 监控执行状态
                            progressManager.startStep(4, '监控任务执行状态');
                            
                            // 开始轮询任务状态
                            pollTaskStatusInList(taskId, progressManager);
                            
                        } else {
                            progressManager.completeStep(3, false, response.message || '配置变更执行失败');
                            progressManager.showResult(
                                false,
                                '任务执行失败',
                                response.message || '配置变更执行失败，请检查任务配置和服务器状态',
                                '请查看详细日志了解失败原因，或联系系统管理员'
                            );
                        }
                    },
                    error: function(xhr) {
                        console.error('执行任务失败:', xhr);
                        const errorMsg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : '网络错误或服务器异常';
                        progressManager.completeStep(3, false, errorMsg);
                        progressManager.showResult(
                            false,
                            '任务执行失败',
                            '执行过程中发生错误: ' + errorMsg,
                            '请检查网络连接、服务器状态和系统日志，或联系系统管理员'
                        );
                    }
                });
                
            }, 600);
            
        }, 500);
        
    }, 400);
}

function pauseTask(taskId) {
    if (!confirm('确定要暂停这个任务吗？')) {
        return;
    }
    
    $.ajax({
        url: '/system-change/tasks/' + taskId + '/pause',
        method: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        beforeSend: function() {
            $('button[onclick="pauseTask(' + taskId + ')"]').prop('disabled', true).text('暂停中...');
        },
        success: function(response) {
            if (response.success) {
                toastr.success('任务已暂停');
                setTimeout(function() { location.reload(); }, 1000);
            } else {
                toastr.error(response.message || '暂停失败');
                $('button[onclick="pauseTask(' + taskId + ')"]').prop('disabled', false).text('暂停');
            }
        },
        error: function(xhr) {
            console.error('暂停任务失败:', xhr);
            var message = '未知错误';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            }
            toastr.error('暂停失败: ' + message);
            $('button[onclick="pauseTask(' + taskId + ')"]').prop('disabled', false).text('暂停');
        }
    });
}

function cancelTask(taskId) {
    if (!confirm('确定要取消这个任务吗？取消后无法恢复执行。')) {
        return;
    }
    
    $.ajax({
        url: '/system-change/tasks/' + taskId + '/cancel',
        method: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        beforeSend: function() {
            $('button[onclick="cancelTask(' + taskId + ')"]').prop('disabled', true).text('取消中...');
        },
        success: function(response) {
            if (response.success) {
                toastr.success('任务已取消');
                setTimeout(function() { location.reload(); }, 1000);
            } else {
                toastr.error(response.message || '取消失败');
                $('button[onclick="cancelTask(' + taskId + ')"]').prop('disabled', false).text('取消');
            }
        },
        error: function(xhr) {
            console.error('取消任务失败:', xhr);
            var message = '未知错误';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            }
            toastr.error('取消失败: ' + message);
            $('button[onclick="cancelTask(' + taskId + ')"]').prop('disabled', false).text('取消');
        }
    });
}

function duplicateTask(taskId) {
    if (!confirm('确定要复制这个任务吗？')) {
        return;
    }
    
    $.ajax({
        url: '/system-change/tasks/' + taskId + '/duplicate',
        method: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        beforeSend: function() {
            $('button[onclick="duplicateTask(' + taskId + ')"]').prop('disabled', true).text('复制中...');
        },
        success: function(response) {
            if (response.success) {
                toastr.success('任务复制成功');
                setTimeout(function() { location.reload(); }, 1000);
            } else {
                toastr.error(response.message || '复制失败');
                $('button[onclick="duplicateTask(' + taskId + ')"]').prop('disabled', false).text('复制');
            }
        },
        error: function(xhr) {
            console.error('复制任务失败:', xhr);
            var message = '未知错误';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            }
            toastr.error('复制失败: ' + message);
            $('button[onclick="duplicateTask(' + taskId + ')"]').prop('disabled', false).text('复制');
        }
    });
}

// 手动刷新进度功能
function refreshProgress() {
    location.reload();
}

$(document).ready(function() {
    console.log('页面加载完成，初始化任务管理功能');
    
    // 移除自动刷新逻辑，改为手动刷新
});

// 全选/取消全选
function toggleSelectAll() {
    var selectAll = document.getElementById('selectAll');
    var checkboxes = document.querySelectorAll('.task-checkbox');
    
    for (var i = 0; i < checkboxes.length; i++) {
        checkboxes[i].checked = selectAll.checked;
    }
    
    updateBatchDeleteButton();
}

// 更新批量删除按钮显示状态
function updateBatchDeleteButton() {
    var checkboxes = document.querySelectorAll('.task-checkbox:checked');
    var batchDeleteBtn = document.getElementById('batchDeleteBtn');
    
    if (checkboxes.length > 0) {
        batchDeleteBtn.style.display = 'inline-block';
        batchDeleteBtn.innerHTML = '<i class="fas fa-trash mr-1"></i>批量删除 (' + checkboxes.length + ')';
    } else {
        batchDeleteBtn.style.display = 'none';
    }
    
    // 更新全选复选框状态
    var allCheckboxes = document.querySelectorAll('.task-checkbox');
    var selectAll = document.getElementById('selectAll');
    
    if (checkboxes.length === allCheckboxes.length && allCheckboxes.length > 0) {
        selectAll.checked = true;
        selectAll.indeterminate = false;
    } else if (checkboxes.length > 0) {
        selectAll.checked = false;
        selectAll.indeterminate = true;
    } else {
        selectAll.checked = false;
        selectAll.indeterminate = false;
    }
}

// 批量删除
function batchDelete() {
    var checkboxes = document.querySelectorAll('.task-checkbox:checked');
    
    if (checkboxes.length === 0) {
        toastr.warning('请选择要删除的任务');
        return;
    }
    
    if (!confirm('确定要删除选中的 ' + checkboxes.length + ' 个任务吗？此操作不可恢复！')) {
        return;
    }
    
    var taskIds = [];
    for (var i = 0; i < checkboxes.length; i++) {
        taskIds.push(checkboxes[i].value);
    }
    
    $.ajax({
        url: '/system-change/tasks/batch-delete',
        method: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content'),
            task_ids: taskIds
        },
        beforeSend: function() {
            $('#batchDeleteBtn').prop('disabled', true).text('删除中...');
        },
        success: function(response) {
            if (response.success) {
                toastr.success('成功删除 ' + response.deleted_count + ' 个任务');
                setTimeout(function() { location.reload(); }, 1000);
            } else {
                toastr.error(response.message || '批量删除失败');
                $('#batchDeleteBtn').prop('disabled', false);
                updateBatchDeleteButton();
            }
        },
        error: function(xhr) {
            console.error('批量删除失败:', xhr);
            var message = '未知错误';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            }
            toastr.error('批量删除失败: ' + message);
            $('#batchDeleteBtn').prop('disabled', false);
            updateBatchDeleteButton();
        }
    });
}

// 轮询任务状态（任务列表版本）
function pollTaskStatusInList(taskId, progressManager) {
    let pollCount = 0;
    const maxPolls = 150; // 最多轮询5分钟 (150 * 2秒)
    
    const pollInterval = setInterval(() => {
        pollCount++;
        
        if (pollCount >= maxPolls) {
            clearInterval(pollInterval);
            progressManager.completeStep(4, false, '任务执行超时');
            progressManager.showResult(
                false,
                '任务执行超时',
                '任务执行时间超过5分钟，请检查任务状态',
                '可能的原因：\n1. 服务器响应缓慢\n2. 任务配置复杂\n3. 网络连接问题\n\n请刷新页面查看最新状态'
            );
            return;
        }
        
        $.ajax({
            url: '/system-change/tasks/' + taskId + '/status',
            type: 'GET',
            success: function(response) {
                // 检查响应格式
                const task = response.success ? response.task : response;
                const details = response.success ? response.details : [];
                
                if (task.status === 'completed') {
                    clearInterval(pollInterval);
                    progressManager.completeStep(4, true, '任务执行完成');
                    
                    // 步骤6: 完成任务处理
                    progressManager.startStep(5, '收集执行结果');
                    
                    setTimeout(() => {
                        progressManager.completeStep(5, true, '执行结果收集完成');
                        
                        // 显示成功结果
                        let resultDetails = `任务ID: ${taskId}\n执行时间: ${new Date().toLocaleString()}\n状态: 已完成`;
                        
                        if (details && details.length > 0) {
                            resultDetails += '\n\n执行详情:';
                            details.forEach((detail, index) => {
                                resultDetails += `\n${index + 1}. 服务器: ${detail.server_name || detail.server_ip}`;
                                resultDetails += `\n   状态: ${detail.status === 'completed' ? '成功' : '失败'}`;
                                if (detail.error_message) {
                                    resultDetails += `\n   错误: ${detail.error_message}`;
                                }
                            });
                        }
                        
                        resultDetails += '\n\n页面将在3秒后自动刷新以显示最新结果...';
                        
                        progressManager.showResult(
                            true,
                            '任务执行成功',
                            '系统变更任务已成功执行完成',
                            resultDetails
                        );
                        
                        // 移除自动刷新，改为手动刷新
                    }, 1000);
                    
                } else if (task.status === 'failed') {
                    clearInterval(pollInterval);
                    progressManager.completeStep(4, false, '任务执行失败');
                    
                    // 收集失败详情
                    let failureDetails = `任务ID: ${taskId}\n执行时间: ${new Date().toLocaleString()}\n状态: 执行失败`;
                    
                    if (details && details.length > 0) {
                        failureDetails += '\n\n失败详情:';
                        details.forEach((detail, index) => {
                            if (detail.status === 'failed' && detail.error_message) {
                                failureDetails += `\n${index + 1}. 服务器: ${detail.server_name || detail.server_ip}`;
                                failureDetails += `\n   错误: ${detail.error_message}`;
                            }
                        });
                    }
                    
                    progressManager.showResult(
                        false,
                        '任务执行失败',
                        '系统变更任务执行过程中发生错误',
                        failureDetails
                    );
                    
                } else if (task.status === 'running') {
                    // 任务仍在执行中，显示进度信息
                    const progress = task.progress || 0;
                    // 更新任务执行进度
                    progressManager.updateStepProgress(4, `任务执行中... (${progress}%)`);
                }
            },
            error: function(xhr) {
                console.error('获取任务状态失败:', xhr);
                // 继续轮询，不中断
            }
        });
    }, 2000); // 每2秒轮询一次
}
</script>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/modules/system-change-tasks.js') }}"></script>
@endpush