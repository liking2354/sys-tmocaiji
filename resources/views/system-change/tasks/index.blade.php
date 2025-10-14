@extends('layouts.app')

@section('title', '系统变更任务')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-tasks mr-2"></i>
                        系统变更任务
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('system-change.tasks.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus mr-1"></i>
                            创建任务
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <!-- 搜索筛选 -->
                    <form method="GET" class="mb-3">
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
                                <a href="{{ route('system-change.tasks.index') }}" class="btn btn-default">
                                    <i class="fas fa-undo mr-1"></i>
                                    重置
                                </a>
                            </div>
                        </div>
                    </form>

                    <!-- 任务列表 -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th width="5%">ID</th>
                                    <th>任务名称</th>
                                    <th width="12%">服务器分组</th>
                                    <th width="8%">服务器数</th>
                                    <th width="8%">模板数</th>
                                    <th width="8%">执行进度</th>
                                    <th width="8%">状态</th>
                                    <th width="10%">执行时间</th>
                                    <th width="12%">创建时间</th>
                                    <th width="15%">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tasks as $task)
                                <tr>
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
                                        <div class="btn-group-vertical btn-group-sm">
                                            <a href="{{ route('system-change.tasks.show', $task) }}" 
                                               class="btn btn-info btn-xs mb-1" title="查看详情">
                                                <i class="fas fa-eye mr-1"></i>详情
                                            </a>
                                            
                                            @if($task->canExecute())
                                                <button type="button" class="btn btn-success btn-xs mb-1" 
                                                        onclick="executeTask({{ $task->id }})" title="执行任务">
                                                    <i class="fas fa-play mr-1"></i>执行
                                                </button>
                                            @endif
                                            
                                            @if($task->canPause())
                                                <button type="button" class="btn btn-warning btn-xs mb-1" 
                                                        onclick="pauseTask({{ $task->id }})" title="暂停任务">
                                                    <i class="fas fa-pause mr-1"></i>暂停
                                                </button>
                                            @endif
                                            
                                            @if($task->canCancel())
                                                <button type="button" class="btn btn-danger btn-xs mb-1" 
                                                        onclick="cancelTask({{ $task->id }})" title="取消任务">
                                                    <i class="fas fa-stop mr-1"></i>取消
                                                </button>
                                            @endif
                                            
                                            @if($task->status === 'pending')
                                                <a href="{{ route('system-change.tasks.edit', $task) }}" 
                                                   class="btn btn-warning btn-xs mb-1" title="编辑">
                                                    <i class="fas fa-edit mr-1"></i>编辑
                                                </a>
                                            @endif
                                            
                                            <form method="POST" action="{{ route('system-change.tasks.duplicate', $task) }}" 
                                                  style="display: inline;">
                                                @csrf
                                                <button type="submit" class="btn btn-secondary btn-xs mb-1" title="复制任务">
                                                    <i class="fas fa-copy mr-1"></i>复制
                                                </button>
                                            </form>
                                            
                                            @if($task->status === 'pending')
                                                <form method="POST" action="{{ route('system-change.tasks.destroy', $task) }}" 
                                                      style="display: inline;" 
                                                      onsubmit="return confirm('确定要删除这个任务吗？')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-xs" title="删除">
                                                        <i class="fas fa-trash mr-1"></i>删除
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="10" class="text-center text-muted">
                                        <i class="fas fa-inbox fa-3x mb-3"></i><br>
                                        暂无系统变更任务
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- 分页 -->
                    <div class="d-flex justify-content-center">
                        {{ $tasks->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.table th {
    white-space: nowrap;
}
.btn-group-vertical .btn {
    border-radius: 0.25rem !important;
    margin-bottom: 2px;
}
.progress {
    background-color: #e9ecef;
}
</style>
@endpush

@push('scripts')
<script>
function executeTask(taskId) {
    if (!confirm('确定要执行这个任务吗？')) {
        return;
    }
    
    $.post(`/system-change/tasks/${taskId}/execute`, {
        _token: $('meta[name="csrf-token"]').attr('content')
    })
    .done(function(response) {
        if (response.success) {
            toastr.success('任务开始执行');
            setTimeout(() => location.reload(), 1000);
        } else {
            toastr.error(response.message || '执行失败');
        }
    })
    .fail(function(xhr) {
        toastr.error('执行失败: ' + (xhr.responseJSON?.message || '未知错误'));
    });
}

function pauseTask(taskId) {
    if (!confirm('确定要暂停这个任务吗？')) {
        return;
    }
    
    $.post(`/system-change/tasks/${taskId}/pause`, {
        _token: $('meta[name="csrf-token"]').attr('content')
    })
    .done(function(response) {
        if (response.success) {
            toastr.success('任务已暂停');
            setTimeout(() => location.reload(), 1000);
        } else {
            toastr.error(response.message || '暂停失败');
        }
    })
    .fail(function(xhr) {
        toastr.error('暂停失败: ' + (xhr.responseJSON?.message || '未知错误'));
    });
}

function cancelTask(taskId) {
    if (!confirm('确定要取消这个任务吗？取消后无法恢复执行。')) {
        return;
    }
    
    $.post(`/system-change/tasks/${taskId}/cancel`, {
        _token: $('meta[name="csrf-token"]').attr('content')
    })
    .done(function(response) {
        if (response.success) {
            toastr.success('任务已取消');
            setTimeout(() => location.reload(), 1000);
        } else {
            toastr.error(response.message || '取消失败');
        }
    })
    .fail(function(xhr) {
        toastr.error('取消失败: ' + (xhr.responseJSON?.message || '未知错误'));
    });
}

// 自动刷新执行中的任务状态
$(document).ready(function() {
    const runningTasks = $('tr').find('.badge-warning, .badge-info').closest('tr');
    
    if (runningTasks.length > 0) {
        // 每30秒刷新一次页面
        setTimeout(() => location.reload(), 30000);
    }
});
</script>
@endpush