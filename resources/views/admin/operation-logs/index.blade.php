@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-list-alt"></i> 操作日志管理</span>
                        <div class="btn-group">
                            <button type="button" class="btn btn-success btn-sm" onclick="exportLogs()">
                                <i class="fas fa-download"></i> 导出日志
                            </button>
                            <button type="button" class="btn btn-warning btn-sm" onclick="showCleanupModal()">
                                <i class="fas fa-broom"></i> 清理日志
                            </button>
                            <button type="button" class="btn btn-danger btn-sm" onclick="batchDelete()">
                                <i class="fas fa-trash"></i> 批量删除
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <!-- 统计信息 -->
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
                                            <i class="fas fa-database fa-2x"></i>
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
                                <button class="btn btn-sm btn-outline-secondary float-end" type="button" data-bs-toggle="collapse" data-bs-target="#searchForm">
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                            </h6>
                        </div>
                        <div class="collapse" id="searchForm">
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
                                            <select class="form-select" id="action" name="action">
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
                                            <select class="form-select" id="user_id" name="user_id">
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
                                        <div class="col-md-6">
                                            <label for="content" class="form-label">操作内容</label>
                                            <input type="text" class="form-control" id="content" name="content" value="{{ request('content') }}" placeholder="搜索操作内容">
                                        </div>
                                        <div class="col-md-2">
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
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th width="50">
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
                                        <td>
                                            <input type="checkbox" class="form-check-input log-checkbox" value="{{ $log->id }}">
                                        </td>
                                        <td>{{ $log->id }}</td>
                                        <td>
                                            @if($log->user)
                                                <span class="badge bg-info">{{ $log->user->username }}</span>
                                            @else
                                                <span class="badge bg-secondary">已删除用户</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $log->action_color }}">{{ $log->action_text }}</span>
                                        </td>
                                        <td>
                                            <div class="text-truncate" style="max-width: 300px;" title="{{ $log->content }}">
                                                {{ $log->content }}
                                            </div>
                                        </td>
                                        <td>
                                            <code>{{ $log->ip }}</code>
                                        </td>
                                        <td>
                                            <small>{{ $log->created_at->format('Y-m-d H:i:s') }}</small>
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.operation-logs.show', $log->id) }}" class="btn btn-sm btn-outline-info" title="查看详情">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            <i class="fas fa-inbox fa-3x mb-3"></i>
                                            <p>暂无日志记录</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- 分页 -->
                    <div class="d-flex justify-content-center">
                        {{ $logs->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 清理日志模态框 -->
<div class="modal fade" id="cleanupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">清理操作日志</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="cleanupForm">
                    <div class="mb-3">
                        <label for="cleanup_days" class="form-label">清理天数前的日志</label>
                        <input type="number" class="form-control" id="cleanup_days" name="days" min="1" max="365" value="30" required>
                        <div class="form-text">将删除指定天数前的所有操作日志，此操作不可恢复</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-warning" onclick="confirmCleanup()">确认清理</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
// 全选/取消全选
document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.log-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
});

// 导出日志
function exportLogs() {
    const params = new URLSearchParams(window.location.search);
    // 移除分页参数，因为导出时不需要分页
    params.delete('page');
    window.location.href = '{{ route("admin.operation-logs.export") }}?' + params.toString();
}

// 显示清理模态框
function showCleanupModal() {
    new bootstrap.Modal(document.getElementById('cleanupModal')).show();
}

// 确认清理
function confirmCleanup() {
    const days = document.getElementById('cleanup_days').value;
    if (!days || days < 1) {
        alert('请输入有效的天数');
        return;
    }

    if (!confirm(`确定要清理 ${days} 天前的所有操作日志吗？此操作不可恢复！`)) {
        return;
    }

    fetch('{{ route("admin.operation-logs.cleanup") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ days: parseInt(days) })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('清理失败：' + (data.message || '未知错误'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('清理失败，请稍后重试');
    });

    bootstrap.Modal.getInstance(document.getElementById('cleanupModal')).hide();
}

// 批量删除
function batchDelete() {
    const checkedBoxes = document.querySelectorAll('.log-checkbox:checked');
    if (checkedBoxes.length === 0) {
        alert('请选择要删除的日志记录');
        return;
    }

    if (!confirm(`确定要删除选中的 ${checkedBoxes.length} 条日志记录吗？此操作不可恢复！`)) {
        return;
    }

    const ids = Array.from(checkedBoxes).map(cb => cb.value);

    fetch('{{ route("admin.operation-logs.batch-delete") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ ids: ids })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('删除失败：' + (data.message || '未知错误'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('删除失败，请稍后重试');
    });
}

// 自动展开搜索表单（如果有搜索参数）
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const hasSearchParams = ['start_date', 'end_date', 'action', 'user_id', 'ip', 'content'].some(param => urlParams.has(param));
    
    if (hasSearchParams) {
        const searchForm = document.getElementById('searchForm');
        new bootstrap.Collapse(searchForm, { show: true });
    }
});
</script>
@endsection