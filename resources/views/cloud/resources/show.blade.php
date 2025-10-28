@extends('layouts.app')

@section('title', '云资源详情')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">云资源详情</h3>
                    <div class="btn-group">
                        <a href="{{ route('cloud.resources.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> 返回列表
                        </a>
                        <button type="button" class="btn btn-primary" onclick="refreshResource()">
                            <i class="fas fa-sync"></i> 刷新数据
                        </button>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row">
                        <!-- 基本信息 -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">基本信息</h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td width="30%" class="text-muted">资源名称：</td>
                                            <td>{{ $resource->resource_name ?: '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">资源ID：</td>
                                            <td><code>{{ $resource->resource_id }}</code></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">云平台：</td>
                                            <td>
                                                <span class="badge bg-primary">{{ $resource->platform->platform_name ?? '-' }}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">资源类型：</td>
                                            <td>{{ $resource->resource_type }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">资源分类：</td>
                                            <td>{{ $resource->resource_category }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">状态：</td>
                                            <td>
                                                @switch($resource->status)
                                                    @case('running')
                                                        <span class="badge bg-success">运行中</span>
                                                        @break
                                                    @case('stopped')
                                                        <span class="badge bg-danger">已停止</span>
                                                        @break
                                                    @case('starting')
                                                        <span class="badge bg-warning">启动中</span>
                                                        @break
                                                    @case('stopping')
                                                        <span class="badge bg-warning">停止中</span>
                                                        @break
                                                    @default
                                                        <span class="badge bg-secondary">{{ $resource->status }}</span>
                                                @endswitch
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">区域：</td>
                                            <td>{{ $resource->region_name ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">创建时间：</td>
                                            <td>{{ $resource->created_at ? $resource->created_at->format('Y-m-d H:i:s') : '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">最后同步：</td>
                                            <td>{{ $resource->last_sync_at ? $resource->last_sync_at->format('Y-m-d H:i:s') : '-' }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- 详细属性 -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">详细属性</h5>
                                </div>
                                <div class="card-body">
                                    @if($resource->attributes && is_array($resource->attributes))
                                        <div class="table-responsive">
                                            <table class="table table-sm table-striped">
                                                @foreach($resource->attributes as $key => $value)
                                                    <tr>
                                                        <td width="40%" class="text-muted">{{ $key }}：</td>
                                                        <td>
                                                            @if(is_array($value))
                                                                <pre class="mb-0"><code>{{ json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                                                            @elseif(is_bool($value))
                                                                <span class="badge bg-{{ $value ? 'success' : 'secondary' }}">
                                                                    {{ $value ? '是' : '否' }}
                                                                </span>
                                                            @else
                                                                {{ $value }}
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </table>
                                        </div>
                                    @else
                                        <div class="text-center text-muted py-3">
                                            <i class="fas fa-info-circle fa-2x mb-2"></i>
                                            <p>暂无详细属性信息</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 原始数据 -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">原始数据 (JSON)</h5>
                                </div>
                                <div class="card-body">
                                    @if($resource->attributes)
                                        <pre><code class="language-json">{{ json_encode($resource->attributes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                                    @else
                                        <div class="text-center text-muted py-3">
                                            <p>暂无原始数据</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 操作历史 -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">操作历史</h5>
                                </div>
                                <div class="card-body">
                                    <div class="text-center text-muted py-3">
                                        <i class="fas fa-history fa-2x mb-2"></i>
                                        <p>操作历史功能待实现</p>
                                    </div>
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

@push('scripts')
<script>
// 刷新资源数据
function refreshResource() {
    if (confirm('确定要刷新这个资源的数据吗？')) {
        // 这里可以实现刷新资源数据的逻辑
        alert('刷新功能待实现');
    }
}

// 代码高亮
document.addEventListener('DOMContentLoaded', function() {
    // 如果有Prism.js或highlight.js，可以在这里初始化代码高亮
    if (typeof Prism !== 'undefined') {
        Prism.highlightAll();
    }
});
</script>
@endpush