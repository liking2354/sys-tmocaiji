@extends('layouts.app')

@section('title', '云资源详情')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- 页面标题 -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">云资源详情</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">首页</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('cloud.resources.index') }}">云资源</a></li>
                            <li class="breadcrumb-item active">{{ $cloudResource->name }}</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="{{ route('cloud.resources.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> 返回列表
                    </a>
                    @if($cloudResource->platform)
                    <button type="button" class="btn btn-primary" onclick="refreshResource()">
                        <i class="fas fa-sync-alt"></i> 刷新资源
                    </button>
                    @endif
                </div>
            </div>

            @if(!$cloudResource->platform)
            <!-- 平台缺失警告 -->
            <div class="alert alert-warning" role="alert">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>警告：</strong> 该资源关联的云平台不存在或已被删除，无法获取详细信息。
            </div>
            @endif

            <div class="row">
                <!-- 基本信息卡片 -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-info-circle"></i> 基本信息
                            </h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-borderless">
                                <tbody>
                                    <tr>
                                        <td class="text-muted" style="width: 120px;">资源名称：</td>
                                        <td><strong>{{ $cloudResource->name }}</strong></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">资源ID：</td>
                                        <td><code>{{ $cloudResource->resource_id }}</code></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">资源类型：</td>
                                        <td>
                                            <span class="badge badge-info">
                                                {{ strtoupper($cloudResource->resource_type) }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">状态：</td>
                                        <td>
                                            @php
                                                $statusClass = match($cloudResource->status) {
                                                    'running' => 'success',
                                                    'stopped' => 'danger',
                                                    'starting' => 'warning',
                                                    'stopping' => 'warning',
                                                    default => 'secondary'
                                                };
                                                $statusText = match($cloudResource->status) {
                                                    'running' => '运行中',
                                                    'stopped' => '已停止',
                                                    'starting' => '启动中',
                                                    'stopping' => '停止中',
                                                    default => $cloudResource->status
                                                };
                                            @endphp
                                            <span class="badge badge-{{ $statusClass }}">
                                                {{ $statusText }}
                                            </span>
                                        </td>
                                    </tr>
                                    @if($cloudResource->platform)
                                    <tr>
                                        <td class="text-muted">云平台：</td>
                                        <td>
                                            <span class="badge badge-primary">
                                                {{ $cloudResource->platform->name }}
                                            </span>
                                        </td>
                                    </tr>
                                    @endif
                                    @if($cloudResource->region)
                                    <tr>
                                        <td class="text-muted">区域：</td>
                                        <td>{{ $cloudResource->region }}</td>
                                    </tr>
                                    @endif
                                    @if($cloudResource->zone)
                                    <tr>
                                        <td class="text-muted">可用区：</td>
                                        <td>{{ $cloudResource->zone }}</td>
                                    </tr>
                                    @endif
                                    <tr>
                                        <td class="text-muted">创建时间：</td>
                                        <td>{{ $cloudResource->created_at ? $cloudResource->created_at->format('Y-m-d H:i:s') : '未知' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">更新时间：</td>
                                        <td>{{ $cloudResource->updated_at ? $cloudResource->updated_at->format('Y-m-d H:i:s') : '未知' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- 详细配置信息 -->
                @if(isset($resourceDetail) && !isset($resourceDetail['error']))
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-cogs"></i> 配置信息
                            </h5>
                        </div>
                        <div class="card-body">
                            @if($cloudResource->resource_type === 'ecs')
                            <!-- ECS 配置信息 -->
                            <table class="table table-borderless">
                                <tbody>
                                    @if(isset($resourceDetail['instance_type']))
                                    <tr>
                                        <td class="text-muted" style="width: 120px;">实例规格：</td>
                                        <td>{{ $resourceDetail['instance_type'] }}</td>
                                    </tr>
                                    @endif
                                    @if(isset($resourceDetail['cpu']))
                                    <tr>
                                        <td class="text-muted">CPU：</td>
                                        <td>{{ $resourceDetail['cpu'] }} 核</td>
                                    </tr>
                                    @endif
                                    @if(isset($resourceDetail['memory']))
                                    <tr>
                                        <td class="text-muted">内存：</td>
                                        <td>{{ $resourceDetail['memory'] }} GB</td>
                                    </tr>
                                    @endif
                                    @if(isset($resourceDetail['os_type']))
                                    <tr>
                                        <td class="text-muted">操作系统：</td>
                                        <td>{{ $resourceDetail['os_type'] }}</td>
                                    </tr>
                                    @endif
                                    @if(isset($resourceDetail['public_ip']))
                                    <tr>
                                        <td class="text-muted">公网IP：</td>
                                        <td>
                                            @if($resourceDetail['public_ip'])
                                                <code>{{ $resourceDetail['public_ip'] }}</code>
                                            @else
                                                <span class="text-muted">无</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endif
                                    @if(isset($resourceDetail['private_ip']))
                                    <tr>
                                        <td class="text-muted">私网IP：</td>
                                        <td><code>{{ $resourceDetail['private_ip'] }}</code></td>
                                    </tr>
                                    @endif
                                    @if(isset($resourceDetail['disk_size']))
                                    <tr>
                                        <td class="text-muted">磁盘大小：</td>
                                        <td>{{ $resourceDetail['disk_size'] }} GB</td>
                                    </tr>
                                    @endif
                                </tbody>
                            </table>
                            @else
                            <!-- 其他资源类型的配置信息 -->
                            <div class="row">
                                @foreach($resourceDetail as $key => $value)
                                    @if(!in_array($key, ['error', 'resource_id']))
                                    <div class="col-sm-6 mb-2">
                                        <strong class="text-muted">{{ ucfirst(str_replace('_', ' ', $key)) }}：</strong>
                                        <span>{{ is_array($value) ? json_encode($value) : $value }}</span>
                                    </div>
                                    @endif
                                @endforeach
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endif

                <!-- 监控信息 -->
                @if($cloudResource->platform && $cloudResource->resource_type === 'ecs')
                <div class="col-12 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-chart-line"></i> 监控信息
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 text-center">
                                    <div class="border rounded p-3">
                                        <h6 class="text-muted">CPU使用率</h6>
                                        <h4 class="text-primary">--</h4>
                                        <small class="text-muted">暂无数据</small>
                                    </div>
                                </div>
                                <div class="col-md-4 text-center">
                                    <div class="border rounded p-3">
                                        <h6 class="text-muted">内存使用率</h6>
                                        <h4 class="text-success">--</h4>
                                        <small class="text-muted">暂无数据</small>
                                    </div>
                                </div>
                                <div class="col-md-4 text-center">
                                    <div class="border rounded p-3">
                                        <h6 class="text-muted">磁盘使用率</h6>
                                        <h4 class="text-warning">--</h4>
                                        <small class="text-muted">暂无数据</small>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 text-center">
                                <a href="{{ route('cloud.resources.monitoring', ['cloudResource' => $cloudResource->id]) }}" class="btn btn-outline-primary">
                                    <i class="fas fa-chart-area"></i> 查看详细监控
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- 操作日志 -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-history"></i> 操作记录
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>时间</th>
                                            <th>操作</th>
                                            <th>状态</th>
                                            <th>备注</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if($cloudResource->created_at)
                                        <tr>
                                            <td>{{ $cloudResource->created_at->format('Y-m-d H:i:s') }}</td>
                                            <td>资源创建</td>
                                            <td><span class="badge badge-success">成功</span></td>
                                            <td>资源已同步到系统</td>
                                        </tr>
                                        @endif
                                        @if($cloudResource->updated_at && $cloudResource->created_at && $cloudResource->updated_at != $cloudResource->created_at)
                                        <tr>
                                            <td>{{ $cloudResource->updated_at->format('Y-m-d H:i:s') }}</td>
                                            <td>信息更新</td>
                                            <td><span class="badge badge-info">完成</span></td>
                                            <td>资源信息已更新</td>
                                        </tr>
                                        @endif
                                        @if(!$cloudResource->created_at && !$cloudResource->updated_at)
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">暂无操作记录</td>
                                        </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 刷新资源模态框 -->
<div class="modal fade" id="refreshModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">刷新资源</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>正在从云平台获取最新的资源信息...</p>
                <div class="progress">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                         role="progressbar" style="width: 100%"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function refreshResource() {
    $('#refreshModal').modal('show');
    
    $.ajax({
        url: '/cloud/resources/{{ $cloudResource->id }}/refresh',
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            $('#refreshModal').modal('hide');
            if (response.success) {
                toastr.success('资源信息刷新成功');
                setTimeout(function() {
                    location.reload();
                }, 1000);
            } else {
                toastr.error(response.message || '刷新失败');
            }
        },
        error: function(xhr) {
            $('#refreshModal').modal('hide');
            let message = '刷新失败';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            }
            toastr.error(message);
        }
    });
}

// 页面加载完成后的初始化
$(document).ready(function() {
    // 如果有错误信息，显示提示
    @if(isset($resourceDetail['error']))
    toastr.warning('{{ $resourceDetail["error"] }}');
    @endif
});
</script>
@endsection