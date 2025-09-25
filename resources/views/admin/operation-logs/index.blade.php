@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">操作日志管理</h1>
                <div>
                    <button type="button" class="btn btn-success" onclick="showExportModal()">
                        <i class="fas fa-download"></i> 导出日志
                    </button>
                    <button type="button" class="btn btn-warning" onclick="showCleanupModal()">
                        <i class="fas fa-trash-alt"></i> 清理日志
                    </button>
                    <button type="button" class="btn btn-danger" onclick="batchDelete()" id="batchDeleteBtn" style="display: none;">
                        <i class="fas fa-trash"></i> 批量删除
                    </button>
                </div>
            </div>

            <!-- 统计卡片 -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4>{{ number_format($stats['total']) }}</h4>
                                    <p class="mb-0">总记录数</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-list fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4>{{ number_format($stats['today']) }}</h4>
                                    <p class="mb-0">今日记录</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-calendar-day fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4>{{ number_format($stats['this_week']) }}</h4>
                                    <p class="mb-0">本周记录</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-calendar-week fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4>{{ number_format($stats['this_month']) }}</h4>
                                    <p class="mb-0">本月记录</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-calendar-alt fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 搜索过滤表单 -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-search"></i> 搜索过滤
                        <button class="btn btn-sm btn-outline-secondary float-right" type="button" data-toggle="collapse" data-target="#searchForm" aria-expanded="{{ request()->hasAny(['start_date', 'end_date', 'action', 'user_id', 'ip', 'content']) ? 'true' : 'false' }}" aria-controls="searchForm" id="searchToggleBtn">
                            <i class="fas fa-chevron-{{ request()->hasAny(['start_date', 'end_date', 'action', 'user_id', 'ip', 'content']) ? 'up' : 'down' }}" id="searchToggleIcon"></i>
                        </button>
                    </h6>
                </div>
                <div class="collapse {{ request()->hasAny(['start_date', 'end_date', 'action', 'user_id', 'ip', 'content']) ? 'show' : '' }}" id="searchForm">
                    <div class="card-body">
                        <form method="GET" action="{{ route('admin.operation-logs.index') }}">
                            <div class="row">
                                <div class="col-md-3">
                                    <label for="start_date" class="form-label">开始日期</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" value="{{ request('start_date') }}">
                                </div>
                                <div class="col-md-3">
                                    <label for="end_date" class="form-label">结束日期</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" value="{{ request('end_date') }}">
                                </div>
                                <div class="col-md-3">
                                    <label for="action" class="form-label">操作类型</label>
                                    <select class="form-control" id="action" name="action">
                                        <option value="">全部</option>
                                        @foreach($actions as $action)
                                            <option value="{{ $action }}" {{ request('action') == $action ? 'selected' : '' }}>
                                                {{ $action }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="user_id" class="form-label">用户</label>
                                    <select class="form-control" id="user_id" name="user_id">
                                        <option value="">全部用户</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                                {{ $user->username }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-4">
                                    <label for="ip" class="form-label">IP地址</label>
                                    <input type="text" class="form-control" id="ip" name="ip" value="{{ request('ip') }}" placeholder="输入IP地址">
                                </div>
                                <div class="col-md-5">
                                    <label for="content" class="form-label">操作内容</label>
                                    <input type="text" class="form-control" id="content" name="content" value="{{ request('content') }}" placeholder="搜索操作内容">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-search"></i> 搜索
                                        </button>
                                        <a href="{{ route('admin.operation-logs.index') }}" class="btn btn-secondary">
                                            <i class="fas fa-undo"></i> 重置
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- 日志列表 -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th width="50" class="text-center">
                                        <input type="checkbox" id="selectAll" class="form-check-input">
                                    </th>
                                    <th width="80">ID</th>
                                    <th width="120">用户</th>
                                    <th width="120">操作类型</th>
                                    <th>操作内容</th>
                                    <th width="120">IP地址</th>
                                    <th width="160">操作时间</th>
                                    <th width="100">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($logs as $log)
                                    <tr>
                                        <td class="text-center">
                                            <input type="checkbox" class="form-check-input log-checkbox" value="{{ $log->id }}">
                                        </td>
                                        <td>{{ $log->id }}</td>
                                        <td>{{ $log->username }}</td>
                                        <td>
                                            <span class="badge badge-{{ $log->action_color }}">
                                                {{ $log->action_text }}
                                            </span>
                                        </td>
                                        <td>{{ Str::limit($log->content, 50) }}</td>
                                        <td>{{ $log->ip }}</td>
                                        <td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                                        <td>
                                            <a href="{{ route('admin.operation-logs.show', $log) }}" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">
                                            <i class="fas fa-inbox fa-3x mb-3"></i>
                                            <p>暂无日志记录</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- 分页 -->
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div>
                            显示第 {{ $logs->firstItem() ?? 0 }} 到 {{ $logs->lastItem() ?? 0 }} 条，共 {{ $logs->total() }} 条记录
                        </div>
                        <div>
                            {{ $logs->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 导出日志模态框 -->
<div class="modal fade" id="exportModal" tabindex="-1" role="dialog" aria-labelledby="exportModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exportModalLabel">导出操作日志</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="exportForm">
                    <div class="row">
                        <div class="col-md-6">
                            <label for="export_start_date" class="form-label">开始日期</label>
                            <input type="date" class="form-control" id="export_start_date" name="start_date" value="{{ now()->subDays(30)->format('Y-m-d') }}">
                        </div>
                        <div class="col-md-6">
                            <label for="export_end_date" class="form-label">结束日期</label>
                            <input type="date" class="form-control" id="export_end_date" name="end_date" value="{{ now()->format('Y-m-d') }}">
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label for="export_action" class="form-label">操作类型</label>
                            <select class="form-control" id="export_action" name="action">
                                <option value="">全部</option>
                                @foreach($actions as $action)
                                    <option value="{{ $action }}">{{ $action }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="export_user_id" class="form-label">用户</label>
                            <select class="form-control" id="export_user_id" name="user_id">
                                <option value="">全部用户</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->username }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <label for="export_content" class="form-label">操作内容</label>
                            <input type="text" class="form-control" id="export_content" name="content" placeholder="搜索操作内容（可选）">
                        </div>
                    </div>
                    <div class="alert alert-info mt-3">
                        <i class="fas fa-info-circle"></i>
                        默认导出最近30天的日志记录。您可以调整上述条件来筛选要导出的数据。
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button>
                <button type="button" class="btn btn-success" onclick="confirmExport()">
                    <i class="fas fa-download"></i> 确认导出
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 清理日志模态框 -->
<div class="modal fade" id="cleanupModal" tabindex="-1" role="dialog" aria-labelledby="cleanupModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cleanupModalLabel">清理历史日志</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="cleanupForm">
                    <div class="form-group">
                        <label for="cleanup_days">清理多少天前的日志</label>
                        <select class="form-control" id="cleanup_days" name="days">
                            <option value="30">30天前</option>
                            <option value="60">60天前</option>
                            <option value="90" selected>90天前</option>
                            <option value="180">180天前</option>
                            <option value="365">365天前</option>
                        </select>
                    </div>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>警告：</strong>此操作将永久删除指定时间之前的所有日志记录，无法恢复！
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button>
                <button type="button" class="btn btn-danger" onclick="confirmCleanup()">
                    <i class="fas fa-trash-alt"></i> 确认清理
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // 搜索表单折叠控制
    $('#searchToggleBtn').on('click', function() {
        var icon = $('#searchToggleIcon');
        var isExpanded = $('#searchForm').hasClass('show');
        
        if (isExpanded) {
            icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
        } else {
            icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
        }
    });

    // 全选/取消全选
    $('#selectAll').on('change', function() {
        var isChecked = $(this).is(':checked');
        $('.log-checkbox').prop('checked', isChecked);
        toggleBatchDeleteBtn();
    });

    // 单个复选框变化
    $('.log-checkbox').on('change', function() {
        var totalCheckboxes = $('.log-checkbox').length;
        var checkedCheckboxes = $('.log-checkbox:checked').length;
        
        $('#selectAll').prop('checked', totalCheckboxes === checkedCheckboxes);
        toggleBatchDeleteBtn();
    });

    // 模态框焦点管理
    $('.modal').on('hide.bs.modal', function () {
        $(this).find(':focus').blur();
    });

    $('.modal').on('shown.bs.modal', function () {
        $(this).find('input, select, textarea, button').filter(':visible').first().focus();
    });
});

// 切换批量删除按钮显示
function toggleBatchDeleteBtn() {
    var checkedCount = $('.log-checkbox:checked').length;
    if (checkedCount > 0) {
        $('#batchDeleteBtn').show();
    } else {
        $('#batchDeleteBtn').hide();
    }
}

// 显示导出模态框
function showExportModal() {
    $('#exportModal').modal('show');
}

// 确认导出
function confirmExport() {
    var formData = $('#exportForm').serialize();
    $('#exportModal').find(':focus').blur();
    $('#exportModal').modal('hide');
    
    // 构建导出URL
    var exportUrl = '{{ route("admin.operation-logs.export") }}?' + formData;
    window.location.href = exportUrl;
}

// 显示清理模态框
function showCleanupModal() {
    $('#cleanupModal').modal('show');
}

// 确认清理
function confirmCleanup() {
    var days = $('#cleanup_days').val();
    
    $.ajax({
        url: '{{ route("admin.operation-logs.cleanup") }}',
        method: 'POST',
        data: {
            days: days,
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            $('#cleanupModal').find(':focus').blur();
            $('#cleanupModal').modal('hide');
            
            if (response.success) {
                alert('清理成功：' + response.message);
                location.reload();
            } else {
                alert('清理失败：' + response.message);
            }
        },
        error: function(xhr) {
            $('#cleanupModal').find(':focus').blur();
            $('#cleanupModal').modal('hide');
            alert('清理失败，请稍后重试');
        }
    });
}

// 批量删除
function batchDelete() {
    var selectedIds = $('.log-checkbox:checked').map(function() {
        return $(this).val();
    }).get();
    
    if (selectedIds.length === 0) {
        alert('请选择要删除的日志记录');
        return;
    }
    
    if (!confirm('确定要删除选中的 ' + selectedIds.length + ' 条日志记录吗？此操作无法撤销！')) {
        return;
    }
    
    $.ajax({
        url: '{{ route("admin.operation-logs.batch-delete") }}',
        method: 'POST',
        data: {
            ids: selectedIds,
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                alert('删除成功：' + response.message);
                location.reload();
            } else {
                alert('删除失败：' + response.message);
            }
        },
        error: function(xhr) {
            alert('删除失败，请稍后重试');
        }
    });
}
</script>
@endsection