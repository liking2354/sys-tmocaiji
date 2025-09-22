@extends('layouts.app')

@section('title', '采集任务管理 - 服务器管理与数据采集系统')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>采集任务管理</h1>
        <div>
            <a href="{{ route('servers.index') }}" class="btn btn-primary mr-2">
                <i class="fas fa-plus"></i> 去服务器页面创建批量任务
            </a>
            <button type="button" class="btn btn-success mr-2" id="batchExecuteBtn">
                <i class="fas fa-play"></i> 立即执行批量任务
            </button>
            <button type="button" class="btn btn-danger" id="batchDeleteBtn" disabled>
                <i class="fas fa-trash"></i> 批量删除
            </button>
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
                            <th>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="selectAllTasks">
                                    <label class="custom-control-label" for="selectAllTasks"></label>
                                </div>
                            </th>
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
                                <td>
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input task-checkbox" id="task_{{ $task->id }}" value="{{ $task->id }}" {{ $task->isRunning() ? 'disabled' : '' }}>
                                        <label class="custom-control-label" for="task_{{ $task->id }}"></label>
                                    </div>
                                </td>
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
                                        @if ($task->status === 2 && $task->error_count > 0)
                                            <button type="button" class="btn btn-sm btn-warning" onclick="retryTask('{{ $task->id }}')">
                                                <i class="fas fa-redo"></i> 重试
                                            </button>
                                        @endif
                                        @if ($task->status === 0 && $task->type !== 'single')
                                            <button type="button" class="btn btn-sm btn-primary" onclick="triggerBatchTask('{{ $task->id }}')">
                                                <i class="fas fa-play"></i> 执行
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
                                <td colspan="10" class="text-center py-3">暂无采集任务</td>
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
        
        // 全选/取消全选
        $('#selectAllTasks').change(function() {
            $('.task-checkbox:not(:disabled)').prop('checked', $(this).prop('checked'));
            updateBatchDeleteButton();
        });
        
        // 单个复选框变化时更新按钮状态
        $('.task-checkbox').change(function() {
            updateBatchDeleteButton();
        });
        
        // 更新批量删除按钮状态
        function updateBatchDeleteButton() {
            var selectedCount = $('.task-checkbox:checked').length;
            $('#batchDeleteBtn').prop('disabled', selectedCount === 0);
        }
        
        // 批量删除按钮点击事件
        $('#batchDeleteBtn').click(function() {
            var selectedIds = [];
            $('.task-checkbox:checked').each(function() {
                selectedIds.push($(this).val());
            });
            
            if (selectedIds.length === 0) {
                toastr.warning('请先选择要删除的任务');
                return;
            }
            
            if (confirm('确定要删除选中的 ' + selectedIds.length + ' 个任务吗？此操作不可恢复！')) {
                $.ajax({
                    url: '{{ route("collection-tasks.batch-destroy") }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        task_ids: selectedIds
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            // 刷新页面
                            window.location.reload();
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function(xhr) {
                        var errorMsg = '批量删除失败';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        }
                        toastr.error(errorMsg);
                    }
                });
            }
        });
        
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
                            toastr.success("任务重试已启动！");
                            location.reload();
                        } else {
                            toastr.error("重试失败：" + response.message);
                        }
                    },
                    error: function(xhr) {
                        toastr.error("请求失败：" + (xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : xhr.responseText));
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
                            toastr.success("任务已取消！");
                            location.reload();
                        } else {
                            toastr.error("取消失败：" + response.message);
                        }
                    },
                    error: function(xhr) {
                        toastr.error("请求失败：" + (xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : xhr.responseText));
                    }
                });
            }
        };
        
        // 手动触发批量任务
        window.triggerBatchTask = function(taskId) {
            if (confirm("确定要手动触发执行此批量任务吗？")) {
                $.ajax({
                    url: "{{ route('collection-tasks.trigger-batch', ':id') }}".replace(":id", taskId),
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success("批量任务已开始执行！");
                            location.reload();
                        } else {
                            toastr.error("触发失败：" + response.message);
                        }
                    },
                    error: function(xhr) {
                        toastr.error("请求失败：" + (xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : xhr.responseText));
                    }
                });
            }
        };
        
        // 立即执行批量任务按钮点击事件
        $('#batchExecuteBtn').click(function() {
            // 获取所有未执行的批量任务
            var pendingBatchTasks = [];
            $('.task-checkbox').each(function() {
                var taskId = $(this).val();
                var taskType = $(this).closest('tr').find('td:nth-child(4) .badge').text().trim();
                var taskStatus = $(this).closest('tr').find('td:nth-child(5) .badge').text().trim();
                
                if (taskType === '批量服务器' && taskStatus === '未开始') {
                    pendingBatchTasks.push({
                        id: taskId,
                        name: $(this).closest('tr').find('td:nth-child(3) a').text().trim()
                    });
                }
            });
            
            if (pendingBatchTasks.length === 0) {
                toastr.warning('没有可执行的未开始批量任务');
                return;
            }
            
            // 构建选择列表
            var taskOptions = '';
            pendingBatchTasks.forEach(function(task) {
                taskOptions += '<option value="' + task.id + '">' + task.name + ' (ID: ' + task.id + ')</option>';
            });
            
            // 显示选择对话框
            var selectDialog = $('<div class="modal fade" tabindex="-1" role="dialog">' +
                '<div class="modal-dialog" role="document">' +
                '<div class="modal-content">' +
                '<div class="modal-header">' +
                '<h5 class="modal-title">选择要执行的批量任务</h5>' +
                '<button type="button" class="close" data-dismiss="modal" aria-label="Close">' +
                '<span aria-hidden="true">&times;</span>' +
                '</button>' +
                '</div>' +
                '<div class="modal-body">' +
                '<div class="form-group">' +
                '<label for="taskSelect">选择任务：</label>' +
                '<select class="form-control" id="taskSelect">' +
                taskOptions +
                '</select>' +
                '</div>' +
                '</div>' +
                '<div class="modal-footer">' +
                '<button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button>' +
                '<button type="button" class="btn btn-primary" id="confirmExecuteBtn">确认执行</button>' +
                '</div>' +
                '</div>' +
                '</div>' +
                '</div>');
            
            $('body').append(selectDialog);
            selectDialog.modal('show');
            
            // 确认执行按钮点击事件
            $('#confirmExecuteBtn').click(function() {
                var taskId = $('#taskSelect').val();
                
                $.ajax({
                    url: '{{ url("collection-tasks") }}/' + taskId + '/trigger',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            selectDialog.modal('hide');
                            // 刷新页面
                            window.location.reload();
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function(xhr) {
                        var errorMsg = '触发任务失败';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        }
                        toastr.error(errorMsg);
                    }
                });
            });
            
            // 模态框关闭时移除
            selectDialog.on('hidden.bs.modal', function() {
                $(this).remove();
            });
        });
    });
</script>
@endsection
