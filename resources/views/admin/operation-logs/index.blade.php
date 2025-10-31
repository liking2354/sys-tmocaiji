@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- 页面标题和操作按钮 -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0">
                <i class="fas fa-history text-primary"></i> 操作日志管理
            </h1>
            <small class="text-muted">查看和管理系统操作日志</small>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <button type="button" class="btn btn-primary btn-sm" onclick="showExportModal()">
                <i class="fas fa-download"></i> 导出日志
            </button>
            <button type="button" class="btn btn-primary btn-sm" onclick="showCleanupModal()">
                <i class="fas fa-trash-alt"></i> 清理日志
            </button>
            <button type="button" class="btn btn-danger btn-sm" onclick="batchDelete()" id="batchDeleteBtn" style="display: none;">
                <i class="fas fa-trash"></i> 批量删除
            </button>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <!-- 统计卡片 -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card card-primary shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-list"></i> 总记录数
                            </h5>
                        </div>
                        <div class="card-body text-center">
                            <h3 class="text-primary">{{ number_format($stats['total']) }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-success shadow-sm">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-calendar-day"></i> 今日记录
                            </h5>
                        </div>
                        <div class="card-body text-center">
                            <h3 class="text-success">{{ number_format($stats['today']) }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-info shadow-sm">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-calendar-week"></i> 本周记录
                            </h5>
                        </div>
                        <div class="card-body text-center">
                            <h3 class="text-info">{{ number_format($stats['this_week']) }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-warning shadow-sm">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0">
                                <i class="fas fa-calendar-alt"></i> 本月记录
                            </h5>
                        </div>
                        <div class="card-body text-center">
                            <h3 class="text-warning">{{ number_format($stats['this_month']) }}</h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 搜索过滤表单 -->
            <div class="search-filter-card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-search"></i> 搜索过滤
                    </h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.operation-logs.index') }}">
                        <div class="search-row">
                            <div>
                                <label for="start_date">开始日期</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" value="{{ request('start_date') }}">
                            </div>
                            <div>
                                <label for="end_date">结束日期</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="{{ request('end_date') }}">
                            </div>
                            <div>
                                <label for="action">操作类型</label>
                                <select class="form-control" id="action" name="action">
                                    <option value="">全部</option>
                                    @foreach($actions as $action)
                                        <option value="{{ $action }}" {{ request('action') == $action ? 'selected' : '' }}>
                                            {{ $action }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="search-row">
                            <div>
                                <label for="user_id">用户</label>
                                <select class="form-control" id="user_id" name="user_id">
                                    <option value="">全部用户</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                            {{ $user->username }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="ip">IP地址</label>
                                <input type="text" class="form-control" id="ip" name="ip" value="{{ request('ip') }}" placeholder="输入IP地址">
                            </div>
                        </div>
                        <div class="button-row">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> 搜索
                            </button>
                            <a href="{{ route('admin.operation-logs.index') }}" class="btn btn-secondary">
                                <i class="fas fa-sync"></i> 重置
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 日志列表 -->
            <div class="card card-light-blue shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list"></i> 操作日志列表
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-light table-hover mb-0">
                            <thead>
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
                                            <a href="{{ route('admin.operation-logs.show', $log) }}" class="btn btn-sm btn-primary">
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
                    <div class="d-flex justify-content-center mt-3 pb-3">
                        {{ $logs->links() }}
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
                <button type="button" class="btn btn-primary" onclick="confirmExport()">
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

@push('scripts')
<script>
    window.exportRoute = '{{ route("admin.operation-logs.export") }}';
    window.cleanupRoute = '{{ route("admin.operation-logs.cleanup") }}';
    window.batchDeleteRoute = '{{ route("admin.operation-logs.batch-delete") }}';
    window.csrfToken = '{{ csrf_token() }}';
</script>
<script src="{{ asset('assets/js/modules/admin-operation-logs.js') }}"></script>
@endpush
