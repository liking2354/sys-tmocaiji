@extends('layouts.app')

@section('title', '采集任务管理 - 服务器管理与数据采集系统')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>采集任务管理</h1>
        <div>
            <a href="{{ route('servers.index') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> 去服务器页面创建批量任务
            </a>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 使用JavaScript设置进度条宽度
            @php
            foreach($tasks as $task) {
                echo "(function() {";
                echo "    var progressBar = document.querySelector('.task-progress-bar-".$task->id."');";
                echo "    if (progressBar) {";
                echo "        progressBar.style.width = '".$task->progress."%';";
                echo "    }";
                echo "})();";
            }
            @endphp
        });
    </script>
    
    <!-- 筛选条件 -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('collection-tasks.index') }}" method="GET" class="form-row align-items-center">
                <div class="col-md-3 mb-2">
                    <label for="status">任务状态</label>
                    <select class="form-control" id="status" name="status">
                        <option value="">所有状态</option>
                        <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>未开始</option>
                        <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>进行中</option>
                        <option value="2" {{ request('status') == '2' ? 'selected' : '' }}>已完成</option>
                        <option value="3" {{ request('status') == '3' ? 'selected' : '' }}>失败</option>
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <label for="type">任务类型</label>
                    <select class="form-control" id="type" name="type">
                        <option value="">所有类型</option>
                        <option value="single" {{ request('type') == 'single' ? 'selected' : '' }}>单服务器</option>
                        <option value="batch" {{ request('type') == 'batch' ? 'selected' : '' }}>批量服务器</option>
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <label for="search">搜索</label>
                    <input type="text" class="form-control" id="search" name="search" placeholder="任务名称" value="{{ request('search') }}">
                </div>
                <div class="col-md-2 mb-2 align-self-end">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-search"></i> 筛选
                    </button>
                </div>
                <div class="col-md-1 mb-2 align-self-end">
                    <a href="{{ route('collection-tasks.index') }}" class="btn btn-secondary btn-block">
                        <i class="fas fa-sync"></i> 重置
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- 任务列表 -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>任务名称</th>
                            <th>类型</th>
                            <th>状态</th>
                            <th>进度</th>
                            <th>服务器数量</th>
                            <th>创建人</th>
                            <th>创建时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tasks as $task)
                            <tr>
                                <td>{{ $task->id }}</td>
                                <td>
                                    <a href="{{ route('collection-tasks.show', $task) }}" class="text-decoration-none">
                                        {{ $task->name }}
                                    </a>
                                    @if ($task->description)
                                        <br><small class="text-muted">{{ $task->description }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-{{ $task->type === 'single' ? 'info' : 'primary' }}">
                                        {{ $task->typeText }}
                                    </span>
                                </td>
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
                                <td>
                                    @if ($task->total_servers > 0)
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar task-progress-bar-{{ $task->id }}
                                                @if($task->progress >= 100) bg-success
                                                @elseif($task->failed_servers > 0) bg-warning
                                                @else bg-info
                                                @endif" 
                                                role="progressbar" 
                                                aria-valuenow="{{ $task->progress }}" 
                                                aria-valuemin="0" 
                                                aria-valuemax="100">
                                                {{ number_format($task->progress, 1) }}%
                                            </div>
                                        </div>
                                        <small class="text-muted">
                                            {{ $task->completed_servers }}/{{ $task->total_servers }}
                                            @if ($task->failed_servers > 0)
                                                (失败: {{ $task->failed_servers }})
                                            @endif
                                        </small>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>{{ $task->total_servers }}</td>
                                <td>{{ $task->creator->username ?? '未知' }}</td>
                                <td>{{ $task->created_at->format('Y-m-d H:i') }}</td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('collection-tasks.show', $task) }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i> 查看
                                        </a>
                                        @if ($task->canRetry())
                                            <button type="button" class="btn btn-sm btn-warning" onclick="retryTask('{{ $task->id }}')">
                                                <i class="fas fa-redo"></i> 重试
                                            </button>
                                        @endif
                                        @if ($task->isRunning())
                                            <button type="button" class="btn btn-sm btn-danger" onclick="cancelTask('{{ $task->id }}')">
                                                <i class="fas fa-stop"></i> 取消
                                            </button>
                                        @endif
                                        @if (!$task->isRunning())
                                            <form action="{{ route('collection-tasks.destroy', $task) }}" method="POST" class="d-inline" onsubmit="return confirm('确定要删除该任务吗？')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i> 删除
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-3">暂无采集任务</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="d-flex justify-content-center mt-3">
                {{ $tasks->appends(request()->query())->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // 自动刷新进行中的任务
    setInterval(function() {
        if ($('.badge-warning').length > 0) {
            location.reload();
        }
    }, 5000); // 每5秒刷新一次
    
    // 重试任务
    window.retryTask = function(taskId) {
        if (confirm("确定要重试失败的任务吗？")) {
            $.ajax({
                url: "{{ route('collection-tasks.retry', ':id') }}".replace(":id", taskId),
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}"
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
    };
    
    // 取消任务
    window.cancelTask = function(taskId) {
        if (confirm("确定要取消正在执行的任务吗？")) {
            $.ajax({
                url: "{{ route('collection-tasks.cancel', ':id') }}".replace(":id", taskId),
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}"
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
    };
});
</script>
@endsection
