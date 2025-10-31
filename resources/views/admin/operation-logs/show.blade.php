@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- 页面标题 -->
    <div class="mb-4">
        <h1 class="mb-1">
            <i class="fas fa-eye text-primary"></i> 操作日志详情
        </h1>
        <p class="text-muted">查看操作日志的详细信息</p>
    </div>
    
    <!-- 操作按钮 -->
    <div class="mb-4">
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.operation-logs.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> 返回列表
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-12">
            <div class="card card-primary shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle"></i> 日志详情
                    </h5>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card card-info shadow-sm h-100">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0"><i class="fas fa-info-circle"></i> 基本信息</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td width="100"><strong>日志ID:</strong></td>
                                            <td><code>{{ $operationLog->id }}</code></td>
                                        </tr>
                                        <tr>
                                            <td><strong>操作用户:</strong></td>
                                            <td>
                                                @if($operationLog->user)
                                                    <span class="badge bg-info">{{ $operationLog->user->username }}</span>
                                                    <small class="text-muted">(ID: {{ $operationLog->user->id }})</small>
                                                @else
                                                    <span class="badge bg-secondary">已删除用户</span>
                                                    <small class="text-muted">(ID: {{ $operationLog->user_id }})</small>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>操作类型:</strong></td>
                                            <td>
                                                <span class="badge bg-{{ $operationLog->action_color }} fs-6">
                                                    {{ $operationLog->action_text }}
                                                </span>
                                                <br>
                                                <small class="text-muted">原始值: {{ $operationLog->action }}</small>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>IP地址:</strong></td>
                                            <td>
                                                <code>{{ $operationLog->ip }}</code>
                                                <button class="btn btn-sm btn-secondary ms-2" onclick="copyToClipboard('{{ $operationLog->ip }}')">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>操作时间:</strong></td>
                                            <td>
                                                <div>
                                                    <i class="fas fa-calendar-alt text-primary"></i>
                                                    {{ $operationLog->created_at->format('Y年m月d日') }}
                                                </div>
                                                <div>
                                                    <i class="fas fa-clock text-success"></i>
                                                    {{ $operationLog->created_at->format('H:i:s') }}
                                                </div>
                                                <small class="text-muted">
                                                    {{ $operationLog->created_at->diffForHumans() }}
                                                </small>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card card-success shadow-sm h-100">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0"><i class="fas fa-file-alt"></i> 操作内容</h6>
                                </div>
                                <div class="card-body">
                                    <div class="border rounded p-3 bg-light" style="min-height: 200px;">
                                        <pre class="mb-0" style="white-space: pre-wrap; word-wrap: break-word;">{{ $operationLog->content }}</pre>
                                    </div>
                                    <div class="mt-3">
                                        <button class="btn btn-sm btn-secondary" onclick="copyToClipboard(`{{ addslashes($operationLog->content) }}`)">
                                            <i class="fas fa-copy"></i> 复制内容
                                        </button>
                                        <span class="text-muted ms-2">
                                            内容长度: {{ mb_strlen($operationLog->content) }} 字符
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($operationLog->user)
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="card card-warning shadow-sm">
                                <div class="card-header bg-warning text-dark">
                                    <h6 class="mb-0"><i class="fas fa-user"></i> 用户详细信息</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <table class="table table-borderless table-sm">
                                                <tr>
                                                    <td><strong>用户名:</strong></td>
                                                    <td>{{ $operationLog->user->username }}</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>邮箱:</strong></td>
                                                    <td>{{ $operationLog->user->email }}</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>状态:</strong></td>
                                                    <td>
                                                        @if($operationLog->user->status)
                                                            <span class="badge bg-success">启用</span>
                                                        @else
                                                            <span class="badge bg-danger">禁用</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                        <div class="col-md-4">
                                            <table class="table table-borderless table-sm">
                                                <tr>
                                                    <td><strong>注册时间:</strong></td>
                                                    <td>{{ $operationLog->user->created_at->format('Y-m-d H:i:s') }}</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>最后登录:</strong></td>
                                                    <td>
                                                        @if($operationLog->user->last_login_time)
                                                            {{ $operationLog->user->last_login_time->format('Y-m-d H:i:s') }}
                                                        @else
                                                            <span class="text-muted">未登录</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                        <div class="col-md-4">
                                            <div>
                                                <strong>用户角色:</strong>
                                                <div class="mt-1">
                                                    @forelse($operationLog->user->roles as $role)
                                                        <span class="badge bg-primary me-1">{{ $role->name }}</span>
                                                    @empty
                                                        <span class="text-muted">无角色</span>
                                                    @endforelse
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- 相关操作日志 -->
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="card card-secondary shadow-sm">
                                <div class="card-header bg-secondary text-white">
                                    <h6 class="mb-0"><i class="fas fa-history"></i> 相关操作记录</h6>
                                </div>
                                <div class="card-body">
                                    @php
                                        $relatedLogs = \App\Models\OperationLog::where('user_id', $operationLog->user_id)
                                            ->where('id', '!=', $operationLog->id)
                                            ->where('created_at', '>=', $operationLog->created_at->subHours(2))
                                            ->where('created_at', '<=', $operationLog->created_at->addHours(2))
                                            ->orderBy('created_at', 'desc')
                                            ->limit(10)
                                            ->get();
                                    @endphp

                                    @if($relatedLogs->count() > 0)
                                        <div class="table-responsive">
                                            <table class="table table-striped table-light table-hover table-sm mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>时间</th>
                                                        <th>操作类型</th>
                                                        <th>操作内容</th>
                                                        <th>IP地址</th>
                                                        <th>操作</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($relatedLogs as $log)
                                                        <tr>
                                                            <td>
                                                                <small>{{ $log->created_at->format('m-d H:i:s') }}</small>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-{{ $log->action_color }} badge-sm">
                                                                    {{ $log->action_text }}
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <div class="text-truncate" style="max-width: 300px;" title="{{ $log->content }}">
                                                                    {{ $log->content }}
                                                                </div>
                                                            </td>
                                                            <td><code>{{ $log->ip }}</code></td>
                                                            <td>
                                                                <div class="btn-group btn-group-sm" role="group">
                                                                    <a href="{{ route('admin.operation-logs.show', $log->id) }}" class="btn btn-primary" title="查看">
                                                                        <i class="fas fa-eye"></i>
                                                                    </a>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="text-center text-muted py-3">
                                            <i class="fas fa-info-circle"></i>
                                            在前后2小时内未找到该用户的其他操作记录
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 操作按钮 -->
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="d-flex justify-content-between flex-wrap gap-2">
                                <div>
                                    <a href="{{ route('admin.operation-logs.index') }}" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left"></i> 返回列表
                                    </a>
                                </div>
                                <div class="d-flex gap-2 flex-wrap">
                                    @if($operationLog->user_id)
                                        <a href="{{ route('admin.operation-logs.index', ['user_id' => $operationLog->user_id]) }}" class="btn btn-primary">
                                            <i class="fas fa-filter"></i> 该用户日志
                                        </a>
                                    @endif
                                    <a href="{{ route('admin.operation-logs.index', ['action' => $operationLog->action]) }}" class="btn btn-primary">
                                        <i class="fas fa-filter"></i> 同类操作
                                    </a>
                                    <a href="{{ route('admin.operation-logs.index', ['ip' => $operationLog->ip]) }}" class="btn btn-primary">
                                        <i class="fas fa-filter"></i> 同IP操作
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        // 显示成功提示
        const toast = document.createElement('div');
        toast.className = 'toast align-items-center text-white bg-success border-0 position-fixed';
        toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999;';
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-check"></i> 已复制到剪贴板
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        document.body.appendChild(toast);
        
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
        
        // 3秒后自动移除
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 3000);
    }).catch(function(err) {
        console.error('复制失败: ', err);
        alert('复制失败，请手动复制');
    });
}
</script>
@endsection