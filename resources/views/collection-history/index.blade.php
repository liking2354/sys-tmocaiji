@extends('layouts.app')

@section('title', '采集历史记录 - 服务器管理与数据采集系统')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>采集历史记录</h1>
    </div>
    
    <!-- 筛选条件 -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('collection-history.index') }}" method="GET" class="form-row align-items-center">
                <div class="col-md-2 mb-2">
                    <label for="server_id">服务器</label>
                    <select class="form-control" id="server_id" name="server_id">
                        <option value="">所有服务器</option>
                        @foreach ($servers as $server)
                            <option value="{{ $server->id }}" {{ request('server_id') == $server->id ? 'selected' : '' }}>
                                {{ $server->name }} ({{ $server->ip }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label for="collector_id">采集组件</label>
                    <select class="form-control" id="collector_id" name="collector_id">
                        <option value="">所有组件</option>
                        @foreach ($collectors as $collector)
                            <option value="{{ $collector->id }}" {{ request('collector_id') == $collector->id ? 'selected' : '' }}>
                                {{ $collector->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label for="status">状态</label>
                    <select class="form-control" id="status" name="status">
                        <option value="">所有状态</option>
                        <option value="2" {{ request('status') == '2' ? 'selected' : '' }}>成功</option>
                        <option value="3" {{ request('status') == '3' ? 'selected' : '' }}>失败</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label for="date_from">开始日期</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2 mb-2">
                    <label for="date_to">结束日期</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-2 mb-2 align-self-end">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-search"></i> 筛选
                    </button>
                </div>
            </form>
            <div class="form-row mt-2">
                <div class="col-md-2">
                    <a href="{{ route('collection-history.index') }}" class="btn btn-secondary btn-block">
                        <i class="fas fa-sync"></i> 重置
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 统计信息 -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h4>{{ $statistics['total'] }}</h4>
                    <p class="mb-0">总采集次数</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h4>{{ $statistics['success'] }}</h4>
                    <p class="mb-0">成功次数</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body text-center">
                    <h4>{{ $statistics['failed'] }}</h4>
                    <p class="mb-0">失败次数</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h4>{{ number_format($statistics['success_rate'], 1) }}%</h4>
                    <p class="mb-0">成功率</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 历史记录列表 -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>服务器</th>
                            <th>采集组件</th>
                            <th>状态</th>
                            <th>执行时间(秒)</th>
                            <th>采集时间</th>
                            <th>关联任务</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($histories as $history)
                            <tr>
                                <td>{{ $history->id }}</td>
                                <td>
                                    <a href="{{ route('servers.show', $history->server) }}" class="text-decoration-none">
                                        <strong>{{ $history->server->name }}</strong>
                                    </a>
                                    <br><small class="text-muted">{{ $history->server->ip }}</small>
                                </td>
                                <td>
                                    <span class="badge badge-{{ $history->collector->type === 'script' ? 'info' : 'warning' }}">
                                        {{ $history->collector->name }}
                                    </span>
                                    <br><small class="text-muted">{{ $history->collector->code }}</small>
                                </td>
                                <td>
                                    @if ($history->status == 2)
                                        <span class="badge badge-success">{{ $history->statusText }}</span>
                                    @else
                                        <span class="badge badge-danger">{{ $history->statusText }}</span>
                                    @endif
                                </td>
                                <td>{{ $history->execution_time > 0 ? number_format($history->execution_time, 3) : '-' }}</td>
                                <td>{{ $history->created_at->format('Y-m-d H:i:s') }}</td>
                                <td>
                                    @if ($history->taskDetail)
                                        <a href="{{ route('collection-tasks.show', $history->taskDetail->task) }}" class="text-decoration-none">
                                            <small>{{ $history->taskDetail->task->name }}</small>
                                        </a>
                                    @else
                                        <span class="text-muted">单独执行</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        @if ($history->hasResult())
                                            <button type="button" class="btn btn-sm btn-info" onclick="viewResult({{ $history->id }})">
                                                <i class="fas fa-eye"></i> 查看结果
                                            </button>
                                        @endif
                                        @if ($history->isFailed() && $history->error_message)
                                            <button type="button" class="btn btn-sm btn-danger" onclick="viewError({{ $history->id }}, '{{ addslashes($history->error_message) }}')">
                                                <i class="fas fa-exclamation-triangle"></i> 查看错误
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-3">暂无采集历史记录</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="d-flex justify-content-center mt-3">
                {{ $histories->appends(request()->query())->links() }}
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
// 查看结果
function viewResult(historyId) {
    $('#resultModal').modal('show');
    $('#resultContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> 加载中...</div>');
    
    $.ajax({
        url: '{{ route("api.collection-history.result", ":id") }}'.replace(':id', historyId),
        type: 'GET',
        success: function(response) {
            if (response.success) {
                var content = '';
                if (typeof response.data.result === 'object') {
                    content = '<pre class="json-formatter">' + JSON.stringify(response.data.result, null, 2) + '</pre>';
                } else {
                    content = '<pre class="bg-light p-3">' + response.data.result + '</pre>';
                }
                $('#resultContent').html(content);
            } else {
                $('#resultContent').html('<div class="alert alert-danger">加载失败：' + response.message + '</div>');
            }
        },
        error: function(xhr) {
            $('#resultContent').html('<div class="alert alert-danger">请求失败：' + xhr.responseText + '</div>');
        }
    });
}

// 查看错误
function viewError(historyId, errorMessage) {
    $('#errorModal').modal('show');
    $('#errorContent').text(errorMessage);
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
