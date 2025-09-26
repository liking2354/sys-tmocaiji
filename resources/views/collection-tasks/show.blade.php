@extends('layouts.app')

@section('title', ($task->name ?? '未知任务') . ' - 采集任务详情')

@section('content')
@if(!$task)
    <div class="container-fluid">
        <div class="alert alert-danger">
            <h4>错误</h4>
            <p>任务不存在或已被删除。</p>
            <a href="{{ route('collection-tasks.index') }}" class="btn btn-primary">返回任务列表</a>
        </div>
    </div>
@else
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>采集任务详情</h1>
        <div>
            <!-- 任务控制按钮 -->
            @if ($task->status == 0)
                <button type="button" class="btn btn-success" onclick="executeTask('{{ $task->id }}')">
                    <i class="fas fa-play"></i> 开始执行
                </button>
            @endif
            
            @if ($task->status == 1)
                <button type="button" class="btn btn-danger" onclick="cancelTask('{{ $task->id }}')">
                    <i class="fas fa-stop"></i> 取消任务
                </button>
            @endif
            
            @if (in_array($task->status, [2, 3]))
                <button type="button" class="btn btn-warning" onclick="resetTask('{{ $task->id }}')">
                    <i class="fas fa-redo"></i> 重置任务
                </button>
            @endif
            
            <button type="button" class="btn btn-info" onclick="refreshTaskStatus()">
                <i class="fas fa-sync"></i> 刷新状态
            </button>
            
            <a href="{{ route('collection-tasks.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> 返回任务列表
            </a>
        </div>
    </div>
    
    <!-- 任务基本信息 -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <i class="fas fa-info-circle"></i> 任务基本信息
                <span class="float-right" id="lastUpdateTime">最后更新: {{ now()->format('H:i:s') }}</span>
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <th style="width: 30%">任务ID:</th>
                            <td>{{ $task->id }}</td>
                        </tr>
                        <tr>
                            <th>任务名称:</th>
                            <td>{{ $task->name }}</td>
                        </tr>
                        <tr>
                            <th>任务描述:</th>
                            <td>{{ $task->description ?: '无描述' }}</td>
                        </tr>
                        <tr>
                            <th>任务类型:</th>
                            <td>
                                <span class="badge badge-{{ $task->type === 'single' ? 'info' : 'primary' }}">
                                    {{ $task->typeText }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>任务状态:</th>
                            <td id="taskStatusDisplay">
                                @include('collection-tasks.partials.status-badge', ['status' => $task->status, 'statusText' => $task->statusText])
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <th style="width: 30%">创建人:</th>
                            <td>{{ $task->creator->username ?? '未知' }}</td>
                        </tr>
                        <tr>
                            <th>创建时间:</th>
                            <td>{{ $task->created_at ? $task->created_at->format('Y-m-d H:i:s') : '未知' }}</td>
                        </tr>
                        <tr>
                            <th>开始时间:</th>
                            <td id="startedAtDisplay">{{ $task->started_at ? $task->started_at->format('Y-m-d H:i:s') : '未开始' }}</td>
                        </tr>
                        <tr>
                            <th>完成时间:</th>
                            <td id="completedAtDisplay">{{ $task->completed_at ? $task->completed_at->format('Y-m-d H:i:s') : '未完成' }}</td>
                        </tr>
                        <tr>
                            <th>执行时长:</th>
                            <td id="durationDisplay">
                                @if ($task->started_at && $task->completed_at)
                                    {{ $task->started_at->diffForHumans($task->completed_at, true) }}
                                @elseif ($task->started_at)
                                    {{ $task->started_at->diffForHumans() }}
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 实时任务进度 -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">
                <i class="fas fa-chart-line"></i> 实时任务进度
                <span class="float-right">
                    <small id="progressUpdateTime">更新时间: {{ now()->format('H:i:s') }}</small>
                </span>
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <div class="progress mb-3" style="height: 30px;">
                        <div class="progress-bar bg-info" id="taskProgressBar" role="progressbar" 
                             style="width: {{ $task->progress }}%" 
                             aria-valuenow="{{ $task->progress }}" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                            <span id="progressText">{{ number_format($task->progress, 1) }}%</span>
                        </div>
                    </div>
                    
                    <!-- 执行日志 -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-list"></i> 执行日志
                                <button class="btn btn-sm btn-outline-secondary float-right" onclick="clearExecutionLog()">
                                    <i class="fas fa-trash"></i> 清空
                                </button>
                            </h6>
                        </div>
                        <div class="card-body p-2">
                            <div id="executionLog" style="height: 200px; overflow-y: auto; background: #f8f9fa; padding: 10px; font-family: monospace; font-size: 12px;">
                                <div class="text-muted">等待任务执行...</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <!-- 统计卡片 -->
                    <div class="row">
                        <div class="col-6 mb-3">
                            <div class="card bg-light text-center">
                                <div class="card-body py-2">
                                    <h4 class="text-primary mb-0" id="totalCount">{{ $stats['total'] ?? 0 }}</h4>
                                    <small class="text-muted">总计</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="card bg-light text-center">
                                <div class="card-body py-2">
                                    <h4 class="text-secondary mb-0" id="pendingCount">{{ $stats['pending'] ?? 0 }}</h4>
                                    <small class="text-muted">未开始</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="card bg-light text-center">
                                <div class="card-body py-2">
                                    <h4 class="text-warning mb-0" id="runningCount">{{ $stats['running'] ?? 0 }}</h4>
                                    <small class="text-muted">进行中</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="card bg-light text-center">
                                <div class="card-body py-2">
                                    <h4 class="text-success mb-0" id="completedCount">{{ $stats['completed'] ?? 0 }}</h4>
                                    <small class="text-muted">已完成</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="card bg-light text-center">
                                <div class="card-body py-2">
                                    <h4 class="text-danger mb-0" id="failedCount">{{ $stats['failed'] ?? 0 }}</h4>
                                    <small class="text-muted">失败</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 任务详情列表 -->
    <div class="card">
        <div class="card-header bg-success text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-list-alt"></i> 执行详情
                </h5>
                <div>
                    <select id="statusFilter" class="form-control form-control-sm" style="width: auto; display: inline-block;">
                        <option value="">所有状态</option>
                        <option value="0">未开始</option>
                        <option value="1">进行中</option>
                        <option value="2">已完成</option>
                        <option value="3">失败</option>
                    </select>
                    <button type="button" class="btn btn-light btn-sm ml-2" onclick="refreshDetails()">
                        <i class="fas fa-sync"></i> 刷新
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-sm" id="detailsTable">
                    <thead>
                        <tr>
                            <th>服务器</th>
                            <th>采集组件</th>
                            <th>状态</th>
                            <th>执行时间(秒)</th>
                            <th>开始时间</th>
                            <th>完成时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody id="detailsTableBody">
                        @forelse ($detailsByServer->flatten()->all() as $detail)
                            <tr data-status="{{ $detail->status }}" data-detail-id="{{ $detail->id }}">
                                <td>
                                    <strong>{{ $detail->server->name }}</strong><br>
                                    <small class="text-muted">{{ $detail->server->ip }}</small>
                                </td>
                                <td>
                                    <span class="badge badge-{{ $detail->collector->type === 'script' ? 'info' : 'warning' }}">
                                        {{ $detail->collector->name }}
                                    </span><br>
                                    <small class="text-muted">{{ $detail->collector->code }}</small>
                                </td>
                                <td class="status-cell">
                                    @include('collection-tasks.partials.status-badge', ['status' => $detail->status, 'statusText' => $detail->statusText])
                                </td>
                                <td class="execution-time">{{ $detail->execution_time > 0 ? number_format($detail->execution_time, 3) : '-' }}</td>
                                <td class="started-at">{{ $detail->started_at ? $detail->started_at->format('H:i:s') : '-' }}</td>
                                <td class="completed-at">{{ $detail->completed_at ? $detail->completed_at->format('H:i:s') : '-' }}</td>
                                <td>
                                    @if ($detail->hasResult())
                                        <button type="button" class="btn btn-sm btn-info" onclick="viewResult('{{ $detail->id }}')">
                                            <i class="fas fa-eye"></i> 查看结果
                                        </button>
                                    @endif
                                    @if ($detail->isFailed() && $detail->error_message)
                                        <button type="button" class="btn btn-sm btn-danger" onclick="viewError('{{ $detail->id }}', '{{ addslashes($detail->error_message) }}')">
                                            <i class="fas fa-exclamation-triangle"></i> 查看错误
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-3">暂无执行详情</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- 结果查看模态框 -->
<div class="modal fade" id="resultModal" tabindex="-1" role="dialog" aria-labelledby="resultModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="resultModalLabel">采集结果</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="resultContent">
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin"></i> 加载中...
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">关闭</button>
            </div>
        </div>
    </div>
</div>

<!-- 错误查看模态框 -->
<div class="modal fade" id="errorModal" tabindex="-1" role="dialog" aria-labelledby="errorModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="errorModalLabel">错误信息</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="errorContent">
                    <pre class="bg-light p-3" style="white-space: pre-wrap;"></pre>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">关闭</button>
            </div>
        </div>
    </div>
</div>
@endif

@endsection

@section('scripts')
<script>
let taskId = {{ $task->id ?? 0 }};
let statusUpdateInterval;
let isExecuting = false;

$(document).ready(function() {
    // 初始化状态筛选
    $('#statusFilter').on('change', function() {
        filterDetailsByStatus($(this).val());
    });
    
    // 如果任务正在执行，启动实时更新
    let taskStatus = {{ $task->status ?? 0 }};
    if (taskStatus == 1) {
        startStatusUpdates();
    }
});

// 执行任务
function executeTask(taskId) {
    if (isExecuting) {
        showAlert('任务正在执行中，请稍候...', 'warning');
        return;
    }
    
    if (!confirm('确定要开始执行这个任务吗？')) {
        return;
    }
    
    isExecuting = true;
    showAlert('正在启动任务执行...', 'info');
    addExecutionLog('开始执行任务 ID: ' + taskId);
    
    $.ajax({
        url: '/task-execution/execute/' + taskId,
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                showAlert(response.message, 'success');
                addExecutionLog('任务启动成功: ' + response.message);
                startStatusUpdates();
            } else {
                showAlert(response.message, 'error');
                addExecutionLog('任务启动失败: ' + response.message);
                isExecuting = false;
            }
        },
        error: function(xhr) {
            let message = '执行失败';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            }
            showAlert(message, 'error');
            addExecutionLog('任务执行错误: ' + message);
            isExecuting = false;
        }
    });
}

// 取消任务
function cancelTask(taskId) {
    if (!confirm('确定要取消这个正在执行的任务吗？')) {
        return;
    }
    
    $.ajax({
        url: '/task-execution/cancel/' + taskId,
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                showAlert(response.message, 'success');
                addExecutionLog('任务已取消: ' + response.message);
                stopStatusUpdates();
                refreshTaskStatus();
            } else {
                showAlert(response.message, 'error');
            }
        },
        error: function(xhr) {
            let message = '取消失败';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            }
            showAlert(message, 'error');
        }
    });
}

// 重置任务
function resetTask(taskId) {
    if (!confirm('确定要重置这个任务吗？重置后任务状态将回到未开始状态。')) {
        return;
    }
    
    $.ajax({
        url: '/task-execution/reset/' + taskId,
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                showAlert(response.message, 'success');
                addExecutionLog('任务已重置: ' + response.message);
                refreshTaskStatus();
                location.reload(); // 重新加载页面以更新所有状态
            } else {
                showAlert(response.message, 'error');
            }
        },
        error: function(xhr) {
            let message = '重置失败';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            }
            showAlert(message, 'error');
        }
    });
}

// 开始状态更新
function startStatusUpdates() {
    if (statusUpdateInterval) {
        clearInterval(statusUpdateInterval);
    }
    
    statusUpdateInterval = setInterval(function() {
        refreshTaskStatus();
    }, 3000); // 每3秒更新一次
    
    addExecutionLog('开始实时状态更新 (每3秒)');
}

// 停止状态更新
function stopStatusUpdates() {
    if (statusUpdateInterval) {
        clearInterval(statusUpdateInterval);
        statusUpdateInterval = null;
        addExecutionLog('停止实时状态更新');
    }
}

// 刷新任务状态
function refreshTaskStatus() {
    $.ajax({
        url: '/task-execution/status/' + taskId,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                updateTaskDisplay(response.data);
                
                // 如果任务完成，停止更新
                if (response.data.status != 1) {
                    stopStatusUpdates();
                    isExecuting = false;
                    addExecutionLog('任务执行完成，状态: ' + response.data.status_text);
                    
                    // 延迟刷新页面，让用户看到完成提示
                    addExecutionLog('正在刷新页面以显示最终结果...');
                    setTimeout(function() {
                        location.reload();
                    }, 3000);
                }
            }
        },
        error: function(xhr) {
            console.error('获取任务状态失败:', xhr);
        }
    });
}

// 更新任务显示
function updateTaskDisplay(taskData) {
    // 更新状态显示
    let statusBadge = getStatusBadge(taskData.status, taskData.status_text);
    $('#taskStatusDisplay').html(statusBadge);
    
    // 更新时间显示
    $('#startedAtDisplay').text(taskData.started_at || '未开始');
    $('#completedAtDisplay').text(taskData.completed_at || '未完成');
    $('#lastUpdateTime').text('最后更新: ' + new Date().toLocaleTimeString());
    $('#progressUpdateTime').text('更新时间: ' + new Date().toLocaleTimeString());
    
    // 更新进度条
    $('#taskProgressBar').css('width', taskData.progress + '%');
    $('#progressText').text(taskData.progress.toFixed(1) + '%');
    
    // 更新统计数据
    $('#totalCount').text(taskData.total);
    $('#pendingCount').text(taskData.pending);
    $('#runningCount').text(taskData.running);
    $('#completedCount').text(taskData.completed);
    $('#failedCount').text(taskData.failed);
    
    // 更新进度条颜色
    let progressBar = $('#taskProgressBar');
    progressBar.removeClass('bg-info bg-success bg-warning bg-danger');
    if (taskData.status == 1) {
        progressBar.addClass('bg-info');
    } else if (taskData.status == 2) {
        progressBar.addClass('bg-success');
    } else if (taskData.status == 3) {
        progressBar.addClass('bg-danger');
    } else {
        progressBar.addClass('bg-secondary');
    }
}

// 获取状态徽章HTML
function getStatusBadge(status, statusText) {
    let badgeClass = 'badge-secondary';
    let icon = '';
    
    switch(status) {
        case 0:
            badgeClass = 'badge-secondary';
            break;
        case 1:
            badgeClass = 'badge-warning';
            icon = '<i class="fas fa-spinner fa-spin"></i> ';
            break;
        case 2:
            badgeClass = 'badge-success';
            break;
        case 3:
            badgeClass = 'badge-danger';
            break;
    }
    
    return '<span class="badge ' + badgeClass + '">' + icon + statusText + '</span>';
}

// 添加执行日志
function addExecutionLog(message) {
    let timestamp = new Date().toLocaleTimeString();
    let logEntry = '[' + timestamp + '] ' + message;
    
    let logContainer = $('#executionLog');
    let currentLog = logContainer.html();
    
    if (currentLog.includes('等待任务执行...')) {
        logContainer.html('<div>' + logEntry + '</div>');
    } else {
        logContainer.append('<div>' + logEntry + '</div>');
    }
    
    // 滚动到底部
    logContainer.scrollTop(logContainer[0].scrollHeight);
}

// 清空执行日志
function clearExecutionLog() {
    $('#executionLog').html('<div class="text-muted">日志已清空</div>');
}

// 按状态筛选详情
function filterDetailsByStatus(status) {
    let rows = $('#detailsTable tbody tr');
    
    if (status === '') {
        rows.show();
    } else {
        rows.each(function() {
            let rowStatus = $(this).data('status');
            if (rowStatus == status) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    }
}

// 刷新详情
function refreshDetails() {
    showAlert('正在刷新详情...', 'info');
    location.reload();
}

// 查看结果
function viewResult(detailId) {
    $('#resultModal').modal('show');
    $('#resultContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> 加载中...</div>');
    
    $.ajax({
        url: '/task-details/' + detailId + '/result',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                let content = '<div class="mb-3">';
                content += '<h6>服务器: ' + response.data.server_name + '</h6>';
                content += '<h6>采集组件: ' + response.data.collector_name + '</h6>';
                content += '<h6>执行时间: ' + response.data.execution_time + ' 秒</h6>';
                content += '</div>';
                content += '<pre class="bg-light p-3" style="max-height: 400px; overflow-y: auto;">';
                content += JSON.stringify(response.data.result, null, 2);
                content += '</pre>';
                $('#resultContent').html(content);
            } else {
                $('#resultContent').html('<div class="alert alert-danger">' + response.message + '</div>');
            }
        },
        error: function() {
            $('#resultContent').html('<div class="alert alert-danger">加载结果失败</div>');
        }
    });
}

// 查看错误
function viewError(detailId, errorMessage) {
    $('#errorModal').modal('show');
    $('#errorContent pre').text(errorMessage);
}

// 显示提示信息
function showAlert(message, type) {
    let alertClass = 'alert-info';
    switch(type) {
        case 'success':
            alertClass = 'alert-success';
            break;
        case 'error':
        case 'danger':
            alertClass = 'alert-danger';
            break;
        case 'warning':
            alertClass = 'alert-warning';
            break;
    }
    
    let alertHtml = '<div class="alert ' + alertClass + ' alert-dismissible fade show" role="alert">';
    alertHtml += message;
    alertHtml += '<button type="button" class="close" data-dismiss="alert" aria-label="Close">';
    alertHtml += '<span aria-hidden="true">&times;</span>';
    alertHtml += '</button>';
    alertHtml += '</div>';
    
    // 移除现有的提示
    $('.alert').remove();
    
    // 添加新提示到页面顶部
    $('.container-fluid').prepend(alertHtml);
    
    // 3秒后自动消失
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 3000);
}

// 页面卸载时清理定时器
$(window).on('beforeunload', function() {
    stopStatusUpdates();
});
</script>
@endsection