@extends('layouts.app')

@section('title', '云资源管理')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">云资源管理</h3>
                    <div class="btn-group">
                        <button type="button" class="btn btn-primary" onclick="showSyncModal()">
                            <i class="fas fa-cloud-download-alt"></i> 同步资源
                        </button>
                        <button type="button" class="btn btn-warning" onclick="showCleanupModal()">
                            <i class="fas fa-trash-alt"></i> 资源清理
                        </button>
                        <button type="button" class="btn btn-info" onclick="exportResources()">
                            <i class="fas fa-download"></i> 导出资源
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- 搜索和筛选 -->
                    <div class="row mb-3">
                        <div class="col-md-2">
                            <select class="form-control" id="platformFilter">
                                <option value="">所有平台</option>
                                @foreach($platforms as $platform)
                                <option value="{{ $platform->id }}">{{ $platform->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-control" id="resourceTypeFilter">
                                <option value="">所有资源类型</option>
                                <option value="ecs">云主机</option>
                                <option value="clb">负载均衡</option>
                                <option value="cdb">MySQL数据库</option>
                                <option value="redis">Redis</option>
                                <option value="domain">域名</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-control" id="regionFilter">
                                <option value="">所有区域</option>
                                @foreach($regions as $region)
                                <option value="{{ $region }}">{{ $region }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-control" id="statusFilter">
                                <option value="">所有状态</option>
                                <option value="running">运行中</option>
                                <option value="stopped">已停止</option>
                                <option value="pending">启动中</option>
                                <option value="error">错误</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="text" class="form-control" id="searchInput" placeholder="搜索资源名称或ID...">
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-primary btn-block" id="searchBtn">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-secondary btn-block" id="resetBtn" title="重置筛选">
                                <i class="fas fa-undo"></i>
                            </button>
                        </div>
                    </div>

                    <!-- 统计信息 -->
                    <div class="row mb-3">
                        <div class="col-md-2">
                            <div class="info-box bg-info">
                                <span class="info-box-icon"><i class="fas fa-server"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">云主机</span>
                                    <span class="info-box-number">{{ $statistics['ecs'] ?? 0 }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="info-box bg-success">
                                <span class="info-box-icon"><i class="fas fa-balance-scale"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">负载均衡</span>
                                    <span class="info-box-number">{{ $statistics['clb'] ?? 0 }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="info-box bg-warning">
                                <span class="info-box-icon"><i class="fas fa-database"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">MySQL</span>
                                    <span class="info-box-number">{{ $statistics['cdb'] ?? 0 }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="info-box bg-danger">
                                <span class="info-box-icon"><i class="fas fa-memory"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Redis</span>
                                    <span class="info-box-number">{{ $statistics['redis'] ?? 0 }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="info-box bg-secondary">
                                <span class="info-box-icon"><i class="fas fa-globe"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">域名</span>
                                    <span class="info-box-number">{{ $statistics['domain'] ?? 0 }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="info-box bg-primary">
                                <span class="info-box-icon"><i class="fas fa-list"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">总计</span>
                                    <span class="info-box-number">{{ array_sum($statistics) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 数据表格 -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="resourcesTable">
                            <thead>
                                <tr>
                                    <th>资源ID</th>
                                    <th>资源名称</th>
                                    <th>资源类型</th>
                                    <th>所属平台</th>
                                    <th>区域</th>
                                    <th>状态</th>
                                    <th>更新时间</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($resources as $resource)
                                <tr>
                                    <td>
                                        <code>{{ $resource->resource_id }}</code>
                                    </td>
                                    <td>{{ $resource->name }}</td>
                                    <td>
                                        @switch($resource->resource_type)
                                            @case('ecs')
                                                <span class="badge badge-info"><i class="fas fa-server"></i> 云主机</span>
                                                @break
                                            @case('clb')
                                                <span class="badge badge-success"><i class="fas fa-balance-scale"></i> 负载均衡</span>
                                                @break
                                            @case('cdb')
                                                <span class="badge badge-warning"><i class="fas fa-database"></i> MySQL</span>
                                                @break
                                            @case('redis')
                                                <span class="badge badge-danger"><i class="fas fa-memory"></i> Redis</span>
                                                @break
                                            @case('domain')
                                                <span class="badge badge-secondary"><i class="fas fa-globe"></i> 域名</span>
                                                @break
                                        @endswitch
                                    </td>
                                    <td>
                                        @switch($resource->platform->platform_type)
                                            @case('huawei')
                                                <span class="badge badge-primary">华为云</span>
                                                @break
                                            @case('alibaba')
                                                <span class="badge badge-warning">阿里云</span>
                                                @break
                                            @case('tencent')
                                                <span class="badge badge-info">腾讯云</span>
                                                @break
                                        @endswitch
                                        <br><small class="text-muted">{{ $resource->platform->name }}</small>
                                    </td>
                                    <td>{{ $resource->region }}</td>
                                    <td>
                                        @php
                                            $statusClass = 'secondary';
                                            $statusIcon = 'question';
                                            switch(strtolower($resource->status)) {
                                                case 'running':
                                                case 'active':
                                                    $statusClass = 'success';
                                                    $statusIcon = 'play';
                                                    break;
                                                case 'stopped':
                                                case 'inactive':
                                                    $statusClass = 'danger';
                                                    $statusIcon = 'stop';
                                                    break;
                                                case 'pending':
                                                case 'starting':
                                                    $statusClass = 'warning';
                                                    $statusIcon = 'spinner';
                                                    break;
                                                case 'error':
                                                case 'failed':
                                                    $statusClass = 'danger';
                                                    $statusIcon = 'exclamation-triangle';
                                                    break;
                                            }
                                        @endphp
                                        <span class="badge badge-{{ $statusClass }}">
                                            <i class="fas fa-{{ $statusIcon }}"></i> {{ $resource->status }}
                                        </span>
                                    </td>
                                    <td>{{ $resource->updated_at->format('Y-m-d H:i:s') }}</td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-info" onclick="viewResourceDetail({{ $resource->id }})">
                                                <i class="fas fa-eye"></i> 详情
                                            </button>
                                            <button type="button" class="btn btn-sm btn-success" onclick="viewResourceMonitoring({{ $resource->id }})">
                                                <i class="fas fa-chart-line"></i> 监控
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center">暂无数据</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- 分页 -->
                    <div class="d-flex justify-content-center">
                        {{ $resources->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 资源详情模态框 -->
<div class="modal fade" id="resourceDetailModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">资源详情</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="resourceDetailContent">
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

<!-- 资源监控模态框 -->
<div class="modal fade" id="resourceMonitoringModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">资源监控</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="resourceMonitoringContent">
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
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // 页面加载时从URL参数恢复筛选条件
    restoreFiltersFromUrl();
    
    // 搜索功能
    $('#searchBtn').click(function() {
        performSearch();
    });

    // 重置筛选
    $('#resetBtn').click(function() {
        resetFilters();
    });

    // 回车搜索
    $('#searchInput').keypress(function(e) {
        if (e.which === 13) {
            performSearch();
        }
    });

    // 移除筛选器自动搜索，只在点击搜索按钮时才执行查询
    // $('#platformFilter, #resourceTypeFilter, #regionFilter, #statusFilter').change(function() {
    //     performSearch();
    // });

    function restoreFiltersFromUrl() {
        const urlParams = new URLSearchParams(window.location.search);
        
        // 恢复筛选条件
        if (urlParams.get('platform_id')) {
            $('#platformFilter').val(urlParams.get('platform_id'));
        }
        if (urlParams.get('resource_type')) {
            $('#resourceTypeFilter').val(urlParams.get('resource_type'));
        }
        if (urlParams.get('region')) {
            $('#regionFilter').val(urlParams.get('region'));
        }
        if (urlParams.get('status')) {
            $('#statusFilter').val(urlParams.get('status'));
        }
        if (urlParams.get('search')) {
            $('#searchInput').val(urlParams.get('search'));
        }
    }

    function performSearch() {
        const platform = $('#platformFilter').val();
        const resourceType = $('#resourceTypeFilter').val();
        const region = $('#regionFilter').val();
        const status = $('#statusFilter').val();
        const search = $('#searchInput').val();
        
        const params = new URLSearchParams();
        if (platform) params.append('platform_id', platform);
        if (resourceType) params.append('resource_type', resourceType);
        if (region) params.append('region', region);
        if (status) params.append('status', status);
        if (search) params.append('search', search);
        
        window.location.href = '{{ route("cloud.resources.index") }}?' + params.toString();
    }

    function resetFilters() {
        // 清空所有筛选条件
        $('#platformFilter').val('');
        $('#resourceTypeFilter').val('');
        $('#regionFilter').val('');
        $('#statusFilter').val('');
        $('#searchInput').val('');
        
        // 跳转到无参数的页面
        window.location.href = '{{ route("cloud.resources.index") }}';
    }
});



// 导出资源
function exportResources() {
    const platform = $('#platformFilter').val();
    const resourceType = $('#resourceTypeFilter').val();
    const region = $('#regionFilter').val();
    const status = $('#statusFilter').val();
    const search = $('#searchInput').val();
    
    const params = new URLSearchParams();
    if (platform) params.append('platform_id', platform);
    if (resourceType) params.append('resource_type', resourceType);
    if (region) params.append('region', region);
    if (status) params.append('status', status);
    if (search) params.append('search', search);
    
    window.open('{{ route("cloud.resources.export") }}?' + params.toString());
}

// 查看资源详情
function viewResourceDetail(resourceId) {
    $('#resourceDetailContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> 加载中...</div>');
    $('#resourceDetailModal').modal('show');
    
    $.ajax({
        url: `/cloud/resources/${resourceId}/detail`,
        method: 'GET',
        success: function(response) {
            let html = '<div class="row">';
            
            // 基本信息
            html += '<div class="col-md-6">';
            html += '<h5>基本信息</h5>';
            html += '<table class="table table-sm">';
            html += `<tr><td><strong>资源ID:</strong></td><td><code>${response.resource_id}</code></td></tr>`;
            html += `<tr><td><strong>资源名称:</strong></td><td>${response.name}</td></tr>`;
            html += `<tr><td><strong>资源类型:</strong></td><td>${getResourceTypeName(response.resource_type)}</td></tr>`;
            html += `<tr><td><strong>状态:</strong></td><td><span class="badge badge-${getStatusClass(response.status)}">${response.status}</span></td></tr>`;
            html += `<tr><td><strong>区域:</strong></td><td>${response.region}</td></tr>`;
            html += `<tr><td><strong>创建时间:</strong></td><td>${response.created_at}</td></tr>`;
            html += `<tr><td><strong>更新时间:</strong></td><td>${response.updated_at}</td></tr>`;
            html += '</table>';
            html += '</div>';
            
            // 元数据信息
            html += '<div class="col-md-6">';
            html += '<h5>元数据信息</h5>';
            if (response.metadata && Object.keys(response.metadata).length > 0) {
                html += '<table class="table table-sm">';
                Object.keys(response.metadata).forEach(key => {
                    const value = response.metadata[key];
                    if (typeof value === 'object') {
                        html += `<tr><td><strong>${key}:</strong></td><td><pre class="small">${JSON.stringify(value, null, 2)}</pre></td></tr>`;
                    } else {
                        html += `<tr><td><strong>${key}:</strong></td><td>${value}</td></tr>`;
                    }
                });
                html += '</table>';
            } else {
                html += '<p class="text-muted">暂无元数据信息</p>';
            }
            html += '</div>';
            
            html += '</div>';
            
            // 原始数据
            html += '<div class="row mt-3">';
            html += '<div class="col-12">';
            html += '<h5>原始数据</h5>';
            html += `<pre class="bg-light p-3 small" style="max-height: 300px; overflow-y: auto;">${JSON.stringify(response.raw_data, null, 2)}</pre>`;
            html += '</div>';
            html += '</div>';
            
            $('#resourceDetailContent').html(html);
        },
        error: function(xhr) {
            $('#resourceDetailContent').html('<div class="alert alert-danger">加载失败：' + (xhr.responseJSON?.message || '网络错误') + '</div>');
        }
    });
}

// 查看资源监控
function viewResourceMonitoring(resourceId) {
    $('#resourceMonitoringContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> 加载中...</div>');
    $('#resourceMonitoringModal').modal('show');
    
    $.ajax({
        url: `/cloud/resources/${resourceId}/monitoring`,
        method: 'GET',
        success: function(response) {
            let html = '<div class="row">';
            
            // 监控概览
            html += '<div class="col-12 mb-3">';
            html += '<h5>监控概览</h5>';
            html += '<div class="row">';
            html += `<div class="col-md-3"><div class="info-box bg-info"><span class="info-box-icon"><i class="fas fa-server"></i></span><div class="info-box-content"><span class="info-box-text">资源ID</span><span class="info-box-number">${response.resource_id}</span></div></div></div>`;
            html += `<div class="col-md-3"><div class="info-box bg-success"><span class="info-box-icon"><i class="fas fa-list"></i></span><div class="info-box-content"><span class="info-box-text">资源类型</span><span class="info-box-number">${getResourceTypeName(response.resource_type)}</span></div></div></div>`;
            html += `<div class="col-md-3"><div class="info-box bg-warning"><span class="info-box-icon"><i class="fas fa-map-marker-alt"></i></span><div class="info-box-content"><span class="info-box-text">区域</span><span class="info-box-number">${response.region}</span></div></div></div>`;
            html += `<div class="col-md-3"><div class="info-box bg-danger"><span class="info-box-icon"><i class="fas fa-clock"></i></span><div class="info-box-content"><span class="info-box-text">更新时间</span><span class="info-box-number small">${response.timestamp}</span></div></div></div>`;
            html += '</div>';
            html += '</div>';
            
            // 监控指标
            html += '<div class="col-12">';
            html += '<h5>监控指标</h5>';
            if (response.metrics && Object.keys(response.metrics).length > 0) {
                html += '<div class="row">';
                Object.keys(response.metrics).forEach(key => {
                    const value = response.metrics[key];
                    const unit = getMetricUnit(key);
                    const icon = getMetricIcon(key);
                    html += `<div class="col-md-3 mb-3">`;
                    html += `<div class="card">`;
                    html += `<div class="card-body text-center">`;
                    html += `<i class="fas fa-${icon} fa-2x text-primary mb-2"></i>`;
                    html += `<h5>${value}${unit}</h5>`;
                    html += `<p class="text-muted">${getMetricName(key)}</p>`;
                    html += `</div>`;
                    html += `</div>`;
                    html += `</div>`;
                });
                html += '</div>';
            } else {
                html += '<p class="text-muted">暂无监控数据</p>';
            }
            html += '</div>';
            
            html += '</div>';
            
            $('#resourceMonitoringContent').html(html);
        },
        error: function(xhr) {
            $('#resourceMonitoringContent').html('<div class="alert alert-danger">加载失败：' + (xhr.responseJSON?.message || '网络错误') + '</div>');
        }
    });
}

// 辅助函数
function getResourceTypeName(type) {
    const types = {
        'ecs': '云主机',
        'clb': '负载均衡',
        'cdb': 'MySQL数据库',
        'redis': 'Redis',
        'domain': '域名'
    };
    return types[type] || type;
}

function getStatusClass(status) {
    const statusMap = {
        'running': 'success',
        'active': 'success',
        'stopped': 'danger',
        'inactive': 'danger',
        'pending': 'warning',
        'starting': 'warning',
        'error': 'danger',
        'failed': 'danger'
    };
    return statusMap[status.toLowerCase()] || 'secondary';
}

function getMetricName(key) {
    const names = {
        'cpu_usage': 'CPU使用率',
        'memory_usage': '内存使用率',
        'disk_usage': '磁盘使用率',
        'network_in': '网络入流量',
        'network_out': '网络出流量',
        'disk_read': '磁盘读取',
        'disk_write': '磁盘写入',
        'active_connections': '活跃连接数',
        'new_connections': '新建连接数',
        'requests_per_second': '每秒请求数',
        'response_time': '响应时间',
        'error_rate': '错误率',
        'connections': '连接数',
        'qps': '每秒查询数',
        'tps': '每秒事务数',
        'ops_per_second': '每秒操作数',
        'hit_rate': '命中率',
        'expired_keys': '过期键数'
    };
    return names[key] || key;
}

function getMetricUnit(key) {
    const units = {
        'cpu_usage': '%',
        'memory_usage': '%',
        'disk_usage': '%',
        'network_in': 'KB/s',
        'network_out': 'KB/s',
        'disk_read': 'KB/s',
        'disk_write': 'KB/s',
        'response_time': 'ms',
        'error_rate': '%',
        'hit_rate': '%'
    };
    return units[key] || '';
}

function getMetricIcon(key) {
    const icons = {
        'cpu_usage': 'microchip',
        'memory_usage': 'memory',
        'disk_usage': 'hdd',
        'network_in': 'download',
        'network_out': 'upload',
        'disk_read': 'arrow-down',
        'disk_write': 'arrow-up',
        'active_connections': 'link',
        'new_connections': 'plus',
        'requests_per_second': 'tachometer-alt',
        'response_time': 'clock',
        'error_rate': 'exclamation-triangle',
        'connections': 'users',
        'qps': 'search',
        'tps': 'exchange-alt',
        'ops_per_second': 'cogs',
        'hit_rate': 'bullseye',
        'expired_keys': 'key'
    };
    return icons[key] || 'chart-bar';
}

// 显示同步资源模态框
function showSyncModal() {
    $('#syncResourceModal').modal('show');
}

// 显示资源清理模态框
function showCleanupModal() {
    $('#cleanupResourceModal').modal('show');
}

// 清理云平台资源
function cleanupPlatformResources() {
    const platformId = $('#cleanup_platform_id').val();
    if (!platformId) {
        toastr.error('请选择要清理的云平台');
        return;
    }

    const btn = $('#cleanupResourceBtn');
    const originalText = btn.html();
    
    btn.prop('disabled', true);
    btn.html('<i class="fas fa-spinner fa-spin"></i> 清理中...');
    
    $.ajax({
        url: '{{ route("cloud.resources.cleanup") }}',
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            platform_id: platformId
        },
        success: function(response) {
            if (response.success) {
                toastr.success(`资源清理成功，共清理 ${response.deleted_count || 0} 个资源`);
                $('#cleanupResourceModal').modal('hide');
                setTimeout(() => location.reload(), 1000);
            } else {
                toastr.error('资源清理失败：' + (response.message || '未知错误'));
            }
        },
        error: function(xhr) {
            toastr.error('资源清理失败：' + (xhr.responseJSON?.message || '网络错误'));
        },
        complete: function() {
            btn.prop('disabled', false);
            btn.html(originalText);
        }
    });
}

// 同步云平台资源
function syncPlatformResources() {
    const platformId = $('#sync_platform_id').val();
    if (!platformId) {
        alert('请选择要同步的云平台');
        return;
    }

    // 显示进度区域，隐藏信息区域
    $('#syncInfo').hide();
    $('#syncProgress').show();
    
    // 禁用按钮
    const syncBtn = $('#syncResourceBtn');
    const cancelBtn = $('[data-dismiss="modal"]');
    syncBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> 同步中...');
    cancelBtn.prop('disabled', true);
    
    // 清空日志
    clearSyncLogs();
    
    // 添加初始日志
    addSyncLog('开始同步云平台资源...', 'info');
    addSyncLog('云平台ID: ' + platformId, 'info');
    
    // 开始同步
    startSyncWithProgress(platformId);
}

// 开始带进度的同步
function startSyncWithProgress(platformId) {
    // 模拟同步步骤
    const steps = [
        { name: '验证云平台配置', progress: 10 },
        { name: '连接云平台API', progress: 20 },
        { name: '获取可用区域列表', progress: 30 },
        { name: '查询ECS实例', progress: 50 },
        { name: '查询RDS实例', progress: 70 },
        { name: '查询Redis实例', progress: 80 },
        { name: '保存资源数据', progress: 90 }
    ];
    
    let currentStep = 0;
    
    function executeStep() {
        if (currentStep >= steps.length) {
            // 执行实际同步
            performActualSync(platformId);
            return;
        }
        
        const step = steps[currentStep];
        addSyncLog('正在执行: ' + step.name, 'info');
        updateSyncProgress(step.progress, step.name);
        
        currentStep++;
        setTimeout(executeStep, 800); // 每步间隔800ms
    }
    
    executeStep();
}

// 执行实际同步
function performActualSync(platformId) {
    addSyncLog('开始执行实际同步操作...', 'info');
    
    $.ajax({
        url: '{{ route("cloud.resources.sync-platform") }}',
        type: 'POST',
        data: {
            platform_id: platformId,
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                addSyncLog('同步成功！', 'success');
                addSyncLog('共同步 ' + (response.data?.total_synced || response.total_count || 0) + ' 个资源', 'success');
                updateSyncProgress(100, '同步完成');
                
                setTimeout(function() {
                    $('#syncResourceModal').modal('hide');
                    location.reload();
                }, 2000);
            } else {
                addSyncLog('同步失败：' + response.message, 'error');
                resetSyncModal();
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            const errorMsg = response ? response.message : '网络错误';
            addSyncLog('同步失败：' + errorMsg, 'error');
            resetSyncModal();
        }
    });
}

// 更新同步进度
function updateSyncProgress(progress, stepName) {
    $('#syncProgressBar').css('width', progress + '%');
    $('#syncProgressText').text(progress + '%');
    
    if (progress === 100) {
        $('#syncProgressBar').removeClass('progress-bar-striped progress-bar-animated')
                            .addClass('bg-success');
    }
}

// 添加同步日志
function addSyncLog(message, type = 'info') {
    const timestamp = new Date().toLocaleTimeString();
    let colorClass = '';
    let icon = '';
    
    switch(type) {
        case 'success':
            colorClass = 'text-success';
            icon = '✓';
            break;
        case 'error':
            colorClass = 'text-danger';
            icon = '✗';
            break;
        case 'warning':
            colorClass = 'text-warning';
            icon = '⚠';
            break;
        default:
            colorClass = 'text-info';
            icon = 'ℹ';
    }
    
    const logEntry = `<div class="${colorClass}">[${timestamp}] ${icon} ${message}</div>`;
    $('#syncLogs').append(logEntry);
    
    // 自动滚动到底部
    const logsContainer = $('#syncLogs')[0];
    logsContainer.scrollTop = logsContainer.scrollHeight;
}

// 清空同步日志
function clearSyncLogs() {
    $('#syncLogs').empty();
}

// 重置同步模态框
function resetSyncModal() {
    $('#syncInfo').show();
    $('#syncProgress').hide();
    
    const syncBtn = $('#syncResourceBtn');
    const cancelBtn = $('[data-dismiss="modal"]');
    syncBtn.prop('disabled', false).html('<i class="fas fa-cloud-download-alt"></i> 开始同步');
    cancelBtn.prop('disabled', false);
    
    // 重置进度条
    updateSyncProgress(0, '');
    $('#syncProgressBar').removeClass('bg-success')
                        .addClass('progress-bar-striped progress-bar-animated');
}
</script>

<!-- 同步资源模态框 -->
<div class="modal fade" id="syncResourceModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">同步云平台资源</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="sync_platform_id">选择云平台</label>
                    <select class="form-control" id="sync_platform_id" required>
                        <option value="">请选择云平台</option>
                        @foreach($platforms as $platform)
                        <option value="{{ $platform->id }}">{{ $platform->name }} ({{ $platform->platform_type }}) - {{ $platform->resources_count ?? 0 }}个资源</option>
                        @endforeach
                    </select>
                </div>
                <div class="alert alert-info" id="syncInfo">
                    <i class="fas fa-info-circle"></i>
                    <strong>注意：</strong>
                    <ul class="mb-0 mt-2">
                        <li>同步操作将从云平台API获取最新的资源信息</li>
                        <li>只会同步运行状态的资源，停止或异常状态的资源不会被同步</li>
                        <li>数据库中的旧资源数据将被清理，只保留最新数据</li>
                        <li>同步过程可能需要几分钟时间，请耐心等待</li>
                    </ul>
                </div>
                
                <!-- 同步进度显示区域 -->
                <div id="syncProgress" style="display: none;">
                    <div class="alert alert-primary">
                        <div class="d-flex align-items-center">
                            <div class="spinner-border spinner-border-sm mr-2" role="status">
                                <span class="sr-only">同步中...</span>
                            </div>
                            <strong>同步进行中...</strong>
                        </div>
                        <div class="progress mt-2" style="height: 20px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                 id="syncProgressBar" role="progressbar" style="width: 0%">
                                <span id="syncProgressText">0%</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 同步日志显示 -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-list-alt"></i> 同步日志
                                <button type="button" class="btn btn-sm btn-outline-secondary float-right" onclick="clearSyncLogs()">
                                    <i class="fas fa-eraser"></i> 清空日志
                                </button>
                            </h6>
                        </div>
                        <div class="card-body p-2">
                            <div id="syncLogs" style="height: 200px; overflow-y: auto; background-color: #f8f9fa; padding: 10px; border-radius: 4px; font-family: 'Courier New', monospace; font-size: 12px;">
                                <!-- 同步日志将在这里显示 -->
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 同步进度显示区域 -->
                <div id="syncProgress" style="display: none;">
                    <div class="alert alert-primary">
                        <div class="d-flex align-items-center">
                            <div class="spinner-border spinner-border-sm mr-2" role="status">
                                <span class="sr-only">同步中...</span>
                            </div>
                            <strong>同步进行中...</strong>
                        </div>
                        <div class="progress mt-2" style="height: 20px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                 id="syncProgressBar" role="progressbar" style="width: 0%">
                                <span id="syncProgressText">0%</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 同步日志显示 -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-list-alt"></i> 同步日志
                                <button type="button" class="btn btn-sm btn-outline-secondary float-right" onclick="clearSyncLogs()">
                                    <i class="fas fa-eraser"></i> 清空日志
                                </button>
                            </h6>
                        </div>
                        <div class="card-body p-2">
                            <div id="syncLogs" style="height: 200px; overflow-y: auto; background-color: #f8f9fa; padding: 10px; border-radius: 4px; font-family: 'Courier New', monospace; font-size: 12px;">
                                <!-- 同步日志将在这里显示 -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" id="syncResourceBtn" onclick="syncPlatformResources()">
                    <i class="fas fa-cloud-download-alt"></i> 开始同步
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 资源清理模态框 -->
<div class="modal fade" id="cleanupResourceModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">清理云平台资源</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="cleanup_platform_id">选择云平台</label>
                    <select class="form-control" id="cleanup_platform_id" required>
                        <option value="">请选择云平台</option>
                        @foreach($platforms as $platform)
                        <option value="{{ $platform->id }}">{{ $platform->name }} ({{ $platform->platform_type }}) - {{ $platform->resources_count ?? 0 }}个资源</option>
                        @endforeach
                    </select>
                </div>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>警告：</strong>
                    <ul class="mb-0 mt-2">
                        <li>此操作将清空所选云平台的所有资源信息</li>
                        <li>清理后的数据无法恢复，请谨慎操作</li>
                        <li>建议在清理前先导出资源数据作为备份</li>
                        <li>清理操作不会影响云平台上的实际资源</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button>
                <button type="button" class="btn btn-warning" id="cleanupResourceBtn" onclick="cleanupPlatformResources()">
                    <i class="fas fa-trash-alt"></i> 确认清理
                </button>
            </div>
        </div>
    </div>
</div>

@endsection