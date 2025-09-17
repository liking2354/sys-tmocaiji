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
            @if ($task->canRetry())
                <button type="button" class="btn btn-warning" onclick="retryTask('{{ $task->id }}')">
                    <i class="fas fa-redo"></i> 重试失败任务
                </button>
            @endif
            @if ($task->isRunning())
                <button type="button" class="btn btn-danger" onclick="cancelTask('{{ $task->id }}')">
                    <i class="fas fa-stop"></i> 取消任务
                </button>
            @endif
            <a href="{{ route('collection-tasks.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> 返回任务列表
            </a>
        </div>
    </div>
    
    <!-- 任务基本信息 -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">任务基本信息</h5>
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
                            <td>
                                @switch($task->status)
                                    @case(0)
                                        <span class="badge badge-secondary">{{ $task->statusText }}</span>
                                        @break
                                    @case(1)
                                        <span class="badge badge-warning">
                                            <i class="fas fa-spinner fa-spin"></i> {{ $task->statusText }}
                                        </span>
                                        @break
                                    @case(2)
                                        <span class="badge badge-success">{{ $task->statusText }}</span>
                                        @break
                                    @case(3)
                                        <span class="badge badge-danger">{{ $task->statusText }}</span>
                                        @break
                                @endswitch
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
                            <td>{{ $task->started_at ? $task->started_at->format('Y-m-d H:i:s') : '未开始' }}</td>
                        </tr>
                        <tr>
                            <th>完成时间:</th>
                            <td>{{ $task->completed_at ? $task->completed_at->format('Y-m-d H:i:s') : '未完成' }}</td>
                        </tr>
                        <tr>
                            <th>执行时长:</th>
                            <td>
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
    
    <!-- 任务进度 -->
    @if ($task->total_servers > 0)
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">任务进度</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            document.querySelector('.task-progress-bar').style.width = '{{ $task->progress }}%';
                        });
                    </script>
                    <div class="progress mb-3" style="height: 30px;">
                        <div class="progress-bar {{ $task->progress >= 100 ? 'bg-success' : ($task->failed_servers > 0 ? 'bg-warning' : 'bg-info') }} task-progress-bar" role="progressbar" aria-valuenow="{{ $task->progress }}" aria-valuemin="0" aria-valuemax="100">
                            {{ number_format($task->progress, 1) }}%
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="card bg-light">
                                <div class="card-body py-2">
                                    <h5 class="text-primary mb-0" id="totalCount">{{ $task->total_servers }}</h5>
                                    <small class="text-muted">总计</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="card bg-light">
                                <div class="card-body py-2">
                                    <h5 class="text-success mb-0" id="completedCount">{{ $task->completed_servers }}</h5>
                                    <small class="text-muted">已完成</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="card bg-light">
                                <div class="card-body py-2">
                                    <h5 class="text-danger mb-0" id="failedCount">{{ $task->failed_servers }}</h5>
                                    <small class="text-muted">失败</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
    
    <!-- 任务详情列表 -->
    <div class="card">
        <div class="card-header bg-success text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">执行详情</h5>
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
                <table class="table table-hover" id="detailsTable">
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
                    <tbody>
                        @forelse ($detailsByServer->flatten()->all() as $detail)
                            <tr data-status="{{ $detail->status }}">
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
                                <td>
                                    @switch($detail->status)
                                        @case(0)
                                            <span class="badge badge-secondary">{{ $detail->statusText }}</span>
                                            @break
                                        @case(1)
                                            <span class="badge badge-warning">
                                                <i class="fas fa-spinner fa-spin"></i> {{ $detail->statusText }}
                                            </span>
                                            @break
                                        @case(2)
                                            <span class="badge badge-success">{{ $detail->statusText }}</span>
                                            @break
                                        @case(3)
                                            <span class="badge badge-danger">{{ $detail->statusText }}</span>
                                            @break
                                    @endswitch
                                </td>
                                <td>{{ $detail->execution_time > 0 ? number_format($detail->execution_time, 3) : '-' }}</td>
                                <td>{{ $detail->started_at ? $detail->started_at->format('H:i:s') : '-' }}</td>
                                <td>{{ $detail->completed_at ? $detail->completed_at->format('H:i:s') : '-' }}</td>
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
<div class="modal fade" id="resultModal" tabindex="-1" role="dialog" aria-labelledby="resultModalLabel" aria-hidden="true">
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
<div class="modal fade" id="errorModal" tabindex="-1" role="dialog" aria-labelledby="errorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="errorModalLabel">错误信息</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <pre id="errorContent" class="bg-light p-3" style="white-space: pre-wrap;"></pre>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">关闭</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // 自动刷新进行中的任务
    var taskIsRunning = "{{ $task->isRunning() ? 'true' : 'false' }}" === "true";
    if (taskIsRunning) {
        window.refreshInterval = setInterval(function() {
            refreshDetails();
        }, 3000); // 每3秒刷新一次
    }
    
    // 状态筛选
    $("#statusFilter").change(function() {
        var status = $(this).val();
        if (status === "") {
            $("#detailsTable tbody tr").show();
        } else {
            $("#detailsTable tbody tr").hide();
            $("#detailsTable tbody tr[data-status=" + status + "]").show();
        }
    });
});
</script>

<script>
// 刷新详情
function refreshDetails() {
    var taskId = "{{ $task->id }}";
    if (taskId) {
        $.ajax({
            url: "{{ route('collection-tasks.progress', ['task' => $task->id ?? 0]) }}",
            type: "GET",
            success: function(response) {
                if (response.success) {
                    // 更新进度条和统计信息
                    updateProgressInfo(response.data);
                    
                    // 获取最新的任务详情数据
                    $.ajax({
                        url: "{{ route('collection-tasks.show', $task->id) }}",
                        type: "GET",
                        headers: {
                            "X-Requested-With": "XMLHttpRequest"
                        },
                        dataType: "json",
                        success: function(response) {
                            if (response.success) {
                                // 更新表格内容
                                updateDetailsTable(response.data.detailsByServer);
                            } else {
                                console.error("获取任务详情失败");
                            }
                            
                            // 如果任务已完成，停止自动刷新
                            if (response.data.status === 2 || response.data.status === 3) {
                                clearInterval(window.refreshInterval);
                            }
                        },
                        error: function(xhr) {
                            console.error("获取任务详情失败：", xhr.responseText);
                        }
                    });
                }
            },
            error: function(xhr) {
                console.error("刷新失败：", xhr.responseText);
            }
        });
    } else {
        console.error("任务ID不存在，无法刷新");
    }
}

// 更新任务详情表格
function updateDetailsTable(detailsByServer) {
    var tbody = $("#detailsTable tbody");
    tbody.empty();
    
    // 检查是否有数据
    if (!detailsByServer || Object.keys(detailsByServer).length === 0) {
        tbody.html('<tr><td colspan="5" class="text-center">暂无数据</td></tr>');
        return;
    }
    
    // 遍历服务器和任务详情
    Object.keys(detailsByServer).forEach(function(serverId) {
        var details = detailsByServer[serverId];
        if (Array.isArray(details) && details.length > 0) {
            details.forEach(function(detail) {
                var statusClass = '';
                var statusText = '';
                
                switch(detail.status) {
                    case 0:
                        statusClass = 'badge-secondary';
                        statusText = '未开始';
                        break;
                    case 1:
                        statusClass = 'badge-warning';
                        statusText = '<i class="fas fa-spinner fa-spin"></i> 进行中';
                        break;
                    case 2:
                        statusClass = 'badge-success';
                        statusText = '已完成';
                        break;
                    case 3:
                        statusClass = 'badge-danger';
                        statusText = '失败';
                        break;
                }
                
                var row = '<tr data-status="' + detail.status + '">' +
                    '<td>' + (detail.server ? detail.server.name : '未知服务器') + '</td>' +
                    '<td>' + (detail.collector ? detail.collector.name : '未知组件') + '</td>' +
                    '<td><span class="badge ' + statusClass + '">' + statusText + '</span></td>' +
                    '<td>' + (detail.created_at ? detail.created_at : '-') + '</td>' +
                    '<td>';
                
                if (detail.status === 2) {
                    row += '<button type="button" class="btn btn-sm btn-info" onclick="viewResult(' + detail.id + ')">查看结果</button>';
                } else if (detail.status === 3) {
                    row += '<button type="button" class="btn btn-sm btn-danger" onclick="viewError(' + detail.id + ', \'' + (detail.error_message ? detail.error_message.replace(/'/g, "\\'") : '无错误信息') + '\')">' +
                        '查看错误</button>';
                } else {
                    row += '-';
                }
                
                row += '</td></tr>';
                tbody.append(row);
            });
        }
    });
    
    // 应用当前的状态筛选
    var currentFilter = $("#statusFilter").val();
    if (currentFilter !== "") {
        $("#detailsTable tbody tr").hide();
        $("#detailsTable tbody tr[data-status=" + currentFilter + "]").show();
    }
}

// 更新进度信息
function updateProgressInfo(data) {
    // 更新进度条
    $(".progress-bar").css("width", data.progress + "%").attr("aria-valuenow", data.progress);
    $(".progress-bar").text(data.progress + "%");
    
    // 更新统计卡片
    $("#totalCount").text(data.total);
    $("#completedCount").text(data.completed);
    $("#failedCount").text(data.failed);
    
    // 更新任务状态
    var statusText = "";
    var statusClass = "";
    
    switch(data.status) {
        case 0:
            statusText = "未开始";
            statusClass = "badge-secondary";
            break;
        case 1:
            statusText = "进行中";
            statusClass = "badge-warning";
            break;
        case 2:
            statusText = "已完成";
            statusClass = "badge-success";
            break;
        case 3:
            statusText = "失败";
            statusClass = "badge-danger";
            break;
    }
    
    // 更新状态显示
    var statusBadge = '<span class="badge ' + statusClass + '">';
    if (data.status === 1) {
        statusBadge += '<i class="fas fa-spinner fa-spin"></i> ';
    }
    statusBadge += statusText + '</span>';
    
    $("td:contains('任务状态')").next().html(statusBadge);
}

// 查看结果
function viewResult(detailId) {
    $("#resultModal").modal("show");
    $("#resultContent").html("<div class=\"text-center\"><i class=\"fas fa-spinner fa-spin\"></i> 加载中...</div>");
    
    var resultUrl = "{{ route('task-details.result', ':id') }}";
    $.ajax({
        url: resultUrl.replace(":id", detailId),
        type: "GET",
        success: function(response) {
            if (response.success) {
                var content = "";
                if (typeof response.data.result === "object") {
                    content = "<pre class=\"json-formatter\">" + JSON.stringify(response.data.result, null, 2) + "</pre>";
                } else {
                    content = "<pre class=\"bg-light p-3\">" + response.data.result + "</pre>";
                }
                $("#resultContent").html(content);
            } else {
                $("#resultContent").html("<div class=\"alert alert-danger\">加载失败：" + response.message + "</div>");
            }
        },
        error: function(xhr) {
            $("#resultContent").html("<div class=\"alert alert-danger\">请求失败：" + xhr.responseText + "</div>");
        }
    });
}

// 查看错误
function viewError(detailId, errorMessage) {
    $("#errorModal").modal("show");
    $("#errorContent").text(errorMessage);
}

// 重试任务
function retryTask(taskId) {
    if (confirm("确定要重试失败的任务吗？")) {
        var retryUrl = "{{ route('collection-tasks.retry', ':id') }}";
        var csrfToken = "{{ csrf_token() }}";
        $.ajax({
            url: retryUrl.replace(":id", taskId),
            type: "POST",
            data: {
                _token: csrfToken
            },
            success: function(response) {
                if (response.success) {
                    alert("任务重试已启动！");
                    location.reload();
                } else {
                    alert("重试失败：" + response.message);
                }
            },
            error: function(xhr) {
                alert("请求失败：" + (xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : xhr.responseText));
            }
        });
    }
}

// 取消任务
function cancelTask(taskId) {
    if (confirm("确定要取消正在执行的任务吗？")) {
        var cancelUrl = "{{ route('collection-tasks.cancel', ':id') }}";
        var csrfToken = "{{ csrf_token() }}";
        $.ajax({
            url: cancelUrl.replace(":id", taskId),
            type: "POST",
            data: {
                _token: csrfToken
            },
            success: function(response) {
                if (response.success) {
                    alert("任务已取消！");
                    location.reload();
                } else {
                    alert("取消失败：" + response.message);
                }
            },
            error: function(xhr) {
                alert("请求失败：" + (xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : xhr.responseText));
            }
        });
    }
}
</script>

<style>
.json-formatter {
    background-color: #f8f9fa;
    border: 1px solid #eee;
    border-radius: 4px;
    padding: 15px;
    max-height: 500px;
    overflow-y: auto;
    white-space: pre-wrap;
    font-family: monospace;
    font-size: 13px;
}
</style>
@endsection
@endif
