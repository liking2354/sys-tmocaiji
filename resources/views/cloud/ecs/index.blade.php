@extends('layouts.app')

@section('title', 'ECS云服务器管理')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">ECS云服务器管理</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary btn-sm" onclick="showSyncModal()">
                            <i class="fas fa-sync"></i> 同步资源
                        </button>
                        <button type="button" class="btn btn-info btn-sm" onclick="refreshStatistics()">
                            <i class="fas fa-chart-bar"></i> 刷新统计
                        </button>
                    </div>
                </div>
                
                <!-- 统计信息 -->
                <div class="card-body">
                    <div class="row" id="statisticsRow">
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-info">
                                <div class="inner">
                                    <h3 id="totalInstances">-</h3>
                                    <p>总实例数</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-server"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-success">
                                <div class="inner">
                                    <h3 id="runningInstances">-</h3>
                                    <p>运行中</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-play"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-warning">
                                <div class="inner">
                                    <h3 id="stoppedInstances">-</h3>
                                    <p>已停止</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-stop"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-danger">
                                <div class="inner">
                                    <h3 id="expiringInstances">-</h3>
                                    <p>即将到期</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 筛选条件 -->
                <div class="card-body border-top">
                    <form id="filterForm" class="row">
                        <div class="col-md-3">
                            <label for="platformFilter">云平台</label>
                            <select class="form-control" id="platformFilter" name="platform_id">
                                <option value="">全部平台</option>
                                @foreach($platforms as $platform)
                                <option value="{{ $platform->id }}">{{ $platform->name }} ({{ $platform->platform_name }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="regionFilter">区域</label>
                            <select class="form-control" id="regionFilter" name="region">
                                <option value="">全部区域</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="statusFilter">状态</label>
                            <select class="form-control" id="statusFilter" name="status">
                                <option value="">全部状态</option>
                                <option value="running">运行中</option>
                                <option value="stopped">已停止</option>
                                <option value="starting">启动中</option>
                                <option value="stopping">停止中</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="searchInput">搜索</label>
                            <input type="text" class="form-control" id="searchInput" name="search" placeholder="实例名称或ID">
                        </div>
                        <div class="col-md-2">
                            <label>&nbsp;</label>
                            <div>
                                <button type="button" class="btn btn-primary" onclick="loadEcsResources()">
                                    <i class="fas fa-search"></i> 搜索
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="resetFilters()">
                                    <i class="fas fa-undo"></i> 重置
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- 资源列表 -->
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>实例ID</th>
                                    <th>实例名称</th>
                                    <th>云平台</th>
                                    <th>区域</th>
                                    <th>实例类型</th>
                                    <th>规格</th>
                                    <th>公网IP</th>
                                    <th>私网IP</th>
                                    <th>状态</th>
                                    <th>操作系统</th>
                                    <th>最后同步</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody id="ecsTableBody">
                                <tr>
                                    <td colspan="12" class="text-center">
                                        <i class="fas fa-spinner fa-spin"></i> 加载中...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- 分页 -->
                    <div class="row">
                        <div class="col-sm-12 col-md-5">
                            <div class="dataTables_info" id="tableInfo"></div>
                        </div>
                        <div class="col-sm-12 col-md-7">
                            <div class="dataTables_paginate paging_simple_numbers" id="tablePagination"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 同步资源模态框 -->
<div class="modal fade" id="syncModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">同步ECS资源</h4>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="syncForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="syncPlatform">选择云平台</label>
                        <select class="form-control" id="syncPlatform" name="platform_id" required>
                            <option value="">请选择云平台</option>
                            @foreach($platforms as $platform)
                            <option value="{{ $platform->id }}">{{ $platform->name }} ({{ $platform->platform_name }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="syncRegion">区域（可选）</label>
                        <input type="text" class="form-control" id="syncRegion" name="region" placeholder="留空同步所有区域">
                    </div>
                    <div class="form-group">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="batchSync">
                            <label class="form-check-label" for="batchSync">
                                批量同步所有平台
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-sync"></i> 开始同步
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 资源详情模态框 -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">ECS实例详情</h4>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="detailContent">
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin"></i> 加载中...
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">关闭</button>
                <button type="button" class="btn btn-info" onclick="showMonitoring()">
                    <i class="fas fa-chart-line"></i> 查看监控
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let currentPage = 1;
let currentResourceId = null;

$(document).ready(function() {
    loadEcsResources();
    loadStatistics();
    
    // 绑定筛选条件变化事件
    $('#platformFilter, #regionFilter, #statusFilter').change(function() {
        currentPage = 1;
        loadEcsResources();
    });
    
    // 绑定搜索框回车事件
    $('#searchInput').keypress(function(e) {
        if (e.which === 13) {
            currentPage = 1;
            loadEcsResources();
        }
    });
    
    // 绑定同步表单提交事件
    $('#syncForm').submit(function(e) {
        e.preventDefault();
        syncResources();
    });
    
    // 批量同步复选框变化事件
    $('#batchSync').change(function() {
        if ($(this).is(':checked')) {
            $('#syncPlatform').prop('disabled', true);
        } else {
            $('#syncPlatform').prop('disabled', false);
        }
    });
});

// 加载ECS资源列表
function loadEcsResources(page = 1) {
    currentPage = page;
    
    const formData = new FormData($('#filterForm')[0]);
    formData.append('page', page);
    
    const params = new URLSearchParams(formData).toString();
    
    $.ajax({
        url: '/cloud/ecs/list?' + params,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                renderEcsTable(response.data);
                renderPagination(response.pagination);
            } else {
                showAlert('获取ECS资源列表失败：' + response.message, 'danger');
            }
        },
        error: function(xhr) {
            showAlert('获取ECS资源列表失败：' + (xhr.responseJSON?.message || '网络错误'), 'danger');
        }
    });
}

// 渲染ECS资源表格
function renderEcsTable(resources) {
    const tbody = $('#ecsTableBody');
    tbody.empty();
    
    if (resources.length === 0) {
        tbody.append(`
            <tr>
                <td colspan="12" class="text-center text-muted">
                    <i class="fas fa-inbox"></i> 暂无数据
                </td>
            </tr>
        `);
        return;
    }
    
    resources.forEach(function(resource) {
        const compute = resource.compute_resource || {};
        const statusClass = getStatusClass(resource.status);
        const osIcon = getOsIcon(compute.os_type);
        
        tbody.append(`
            <tr>
                <td>
                    <code>${resource.resource_id}</code>
                </td>
                <td>
                    <strong>${resource.name || '-'}</strong>
                    ${compute.instance_name && compute.instance_name !== resource.name ? 
                        '<br><small class="text-muted">' + compute.instance_name + '</small>' : ''}
                </td>
                <td>
                    <span class="badge badge-info">${resource.platform.platform_name}</span>
                    <br><small class="text-muted">${resource.platform.name}</small>
                </td>
                <td>${resource.region || '-'}</td>
                <td>
                    <code>${compute.instance_type || '-'}</code>
                </td>
                <td>
                    ${compute.cpu_cores || 0}核${compute.memory_gb || 0}GB
                    ${compute.disk_size_gb ? '<br><small class="text-muted">' + compute.disk_size_gb + 'GB存储</small>' : ''}
                </td>
                <td>
                    ${compute.public_ip ? '<code>' + compute.public_ip + '</code>' : '-'}
                </td>
                <td>
                    ${compute.private_ip ? '<code>' + compute.private_ip + '</code>' : '-'}
                </td>
                <td>
                    <span class="badge badge-${statusClass}">${getStatusText(resource.status)}</span>
                    ${compute.instance_status_name ? '<br><small class="text-muted">' + compute.instance_status_name + '</small>' : ''}
                </td>
                <td>
                    <i class="${osIcon}"></i> ${compute.os_type_name || compute.os_name || '-'}
                </td>
                <td>
                    <small>${formatDateTime(resource.last_sync_at)}</small>
                </td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-info" onclick="showResourceDetail(${resource.id})" title="查看详情">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button type="button" class="btn btn-success" onclick="showMonitoring(${resource.id})" title="监控信息">
                            <i class="fas fa-chart-line"></i>
                        </button>
                        <button type="button" class="btn btn-primary" onclick="syncSingleResource(${resource.platform_id}, '${resource.region}')" title="重新同步">
                            <i class="fas fa-sync"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `);
    });
}

// 渲染分页
function renderPagination(pagination) {
    const info = $('#tableInfo');
    const nav = $('#tablePagination');
    
    // 显示信息
    const start = (pagination.current_page - 1) * pagination.per_page + 1;
    const end = Math.min(pagination.current_page * pagination.per_page, pagination.total);
    info.html(`显示 ${start} 到 ${end} 条，共 ${pagination.total} 条记录`);
    
    // 生成分页链接
    nav.empty();
    
    if (pagination.last_page > 1) {
        let paginationHtml = '<ul class="pagination pagination-sm m-0 float-right">';
        
        // 上一页
        if (pagination.current_page > 1) {
            paginationHtml += `<li class="page-item"><a class="page-link" href="#" onclick="loadEcsResources(${pagination.current_page - 1})">上一页</a></li>`;
        }
        
        // 页码
        const startPage = Math.max(1, pagination.current_page - 2);
        const endPage = Math.min(pagination.last_page, pagination.current_page + 2);
        
        if (startPage > 1) {
            paginationHtml += '<li class="page-item"><a class="page-link" href="#" onclick="loadEcsResources(1)">1</a></li>';
            if (startPage > 2) {
                paginationHtml += '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }
        
        for (let i = startPage; i <= endPage; i++) {
            const activeClass = i === pagination.current_page ? 'active' : '';
            paginationHtml += `<li class="page-item ${activeClass}"><a class="page-link" href="#" onclick="loadEcsResources(${i})">${i}</a></li>`;
        }
        
        if (endPage < pagination.last_page) {
            if (endPage < pagination.last_page - 1) {
                paginationHtml += '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
            paginationHtml += `<li class="page-item"><a class="page-link" href="#" onclick="loadEcsResources(${pagination.last_page})">${pagination.last_page}</a></li>`;
        }
        
        // 下一页
        if (pagination.current_page < pagination.last_page) {
            paginationHtml += `<li class="page-item"><a class="page-link" href="#" onclick="loadEcsResources(${pagination.current_page + 1})">下一页</a></li>`;
        }
        
        paginationHtml += '</ul>';
        nav.html(paginationHtml);
    }
}

// 加载统计信息
function loadStatistics() {
    $.ajax({
        url: '/cloud/ecs/statistics',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                const stats = response.data;
                $('#totalInstances').text(stats.total_instances);
                $('#runningInstances').text(stats.running_instances);
                $('#stoppedInstances').text(stats.stopped_instances);
                $('#expiringInstances').text(stats.expiring_instances);
            }
        },
        error: function(xhr) {
            console.error('获取统计信息失败：', xhr.responseJSON?.message || '网络错误');
        }
    });
}

// 刷新统计信息
function refreshStatistics() {
    loadStatistics();
    showAlert('统计信息已刷新', 'success');
}

// 显示同步模态框
function showSyncModal() {
    $('#syncModal').modal('show');
}

// 同步资源
function syncResources() {
    const formData = new FormData($('#syncForm')[0]);
    const isBatchSync = $('#batchSync').is(':checked');
    
    let url = '/cloud/ecs/sync';
    let data = {};
    
    if (isBatchSync) {
        url = '/cloud/ecs/batch-sync';
        data.platform_ids = @json($platforms->pluck('id')->toArray());
        if (formData.get('region')) {
            data.region = formData.get('region');
        }
    } else {
        data.platform_id = formData.get('platform_id');
        if (formData.get('region')) {
            data.region = formData.get('region');
        }
    }
    
    // 显示加载状态
    const submitBtn = $('#syncForm button[type="submit"]');
    const originalText = submitBtn.html();
    submitBtn.html('<i class="fas fa-spinner fa-spin"></i> 同步中...').prop('disabled', true);
    
    $.ajax({
        url: url,
        method: 'POST',
        data: data,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                showAlert(response.message, 'success');
                $('#syncModal').modal('hide');
                loadEcsResources();
                loadStatistics();
            } else {
                showAlert('同步失败：' + response.message, 'danger');
            }
        },
        error: function(xhr) {
            showAlert('同步失败：' + (xhr.responseJSON?.message || '网络错误'), 'danger');
        },
        complete: function() {
            submitBtn.html(originalText).prop('disabled', false);
        }
    });
}

// 单个资源同步
function syncSingleResource(platformId, region) {
    $.ajax({
        url: '/cloud/ecs/sync',
        method: 'POST',
        data: {
            platform_id: platformId,
            region: region
        },
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                showAlert(response.message, 'success');
                loadEcsResources();
                loadStatistics();
            } else {
                showAlert('同步失败：' + response.message, 'danger');
            }
        },
        error: function(xhr) {
            showAlert('同步失败：' + (xhr.responseJSON?.message || '网络错误'), 'danger');
        }
    });
}

// 显示资源详情
function showResourceDetail(resourceId) {
    currentResourceId = resourceId;
    
    $('#detailContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> 加载中...</div>');
    $('#detailModal').modal('show');
    
    $.ajax({
        url: `/cloud/ecs/${resourceId}`,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                renderResourceDetail(response.data);
            } else {
                $('#detailContent').html('<div class="alert alert-danger">加载失败：' + response.message + '</div>');
            }
        },
        error: function(xhr) {
            $('#detailContent').html('<div class="alert alert-danger">加载失败：' + (xhr.responseJSON?.message || '网络错误') + '</div>');
        }
    });
}

// 渲染资源详情
function renderResourceDetail(resource) {
    const compute = resource.compute_resource || {};
    const platform = resource.platform || {};
    
    const html = `
        <div class="row">
            <div class="col-md-6">
                <h5>基本信息</h5>
                <table class="table table-sm">
                    <tr><td>实例ID</td><td><code>${resource.resource_id}</code></td></tr>
                    <tr><td>实例名称</td><td>${resource.name || '-'}</td></tr>
                    <tr><td>云平台</td><td>${platform.platform_name} (${platform.name})</td></tr>
                    <tr><td>区域</td><td>${resource.region || '-'}</td></tr>
                    <tr><td>状态</td><td><span class="badge badge-${getStatusClass(resource.status)}">${getStatusText(resource.status)}</span></td></tr>
                    <tr><td>最后同步</td><td>${formatDateTime(resource.last_sync_at)}</td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h5>配置信息</h5>
                <table class="table table-sm">
                    <tr><td>实例类型</td><td><code>${compute.instance_type || '-'}</code></td></tr>
                    <tr><td>CPU</td><td>${compute.cpu_cores || 0} 核</td></tr>
                    <tr><td>内存</td><td>${compute.memory_gb || 0} GB</td></tr>
                    <tr><td>系统盘</td><td>${compute.disk_size_gb || 0} GB (${compute.disk_type || '-'})</td></tr>
                    <tr><td>操作系统</td><td><i class="${getOsIcon(compute.os_type)}"></i> ${compute.os_name || '-'}</td></tr>
                    <tr><td>镜像ID</td><td><code>${compute.image_id || '-'}</code></td></tr>
                </table>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <h5>网络信息</h5>
                <table class="table table-sm">
                    <tr><td>VPC ID</td><td><code>${compute.vpc_id || '-'}</code></td></tr>
                    <tr><td>子网ID</td><td><code>${compute.subnet_id || '-'}</code></td></tr>
                    <tr><td>公网IP</td><td>${compute.public_ip ? '<code>' + compute.public_ip + '</code>' : '-'}</td></tr>
                    <tr><td>私网IP</td><td>${compute.private_ip ? '<code>' + compute.private_ip + '</code>' : '-'}</td></tr>
                    <tr><td>带宽</td><td>${compute.bandwidth_mbps || 0} Mbps</td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h5>其他信息</h5>
                <table class="table table-sm">
                    <tr><td>计费类型</td><td>${compute.instance_charge_type_name || '-'}</td></tr>
                    <tr><td>创建时间</td><td>${formatDateTime(compute.created_time)}</td></tr>
                    <tr><td>到期时间</td><td>${compute.expired_time ? formatDateTime(compute.expired_time) : '-'}</td></tr>
                    <tr><td>监控</td><td>${compute.monitoring_enabled ? '已启用' : '未启用'}</td></tr>
                    <tr><td>弹性伸缩</td><td>${compute.auto_scaling_enabled ? '已启用' : '未启用'}</td></tr>
                </table>
            </div>
        </div>
        ${compute.tags && Object.keys(compute.tags).length > 0 ? `
        <div class="row">
            <div class="col-12">
                <h5>标签</h5>
                <div>
                    ${Object.entries(compute.tags).map(([key, value]) => 
                        `<span class="badge badge-secondary mr-1">${key}: ${value}</span>`
                    ).join('')}
                </div>
            </div>
        </div>
        ` : ''}
    `;
    
    $('#detailContent').html(html);
}

// 显示监控信息
function showMonitoring(resourceId = null) {
    const id = resourceId || currentResourceId;
    if (!id) return;
    
    // 这里可以打开监控页面或模态框
    showAlert('监控功能开发中...', 'info');
}

// 重置筛选条件
function resetFilters() {
    $('#filterForm')[0].reset();
    currentPage = 1;
    loadEcsResources();
}

// 获取状态样式类
function getStatusClass(status) {
    const statusMap = {
        'running': 'success',
        'stopped': 'secondary',
        'starting': 'info',
        'stopping': 'warning',
        'pending': 'info',
        'terminated': 'danger',
        'unknown': 'dark'
    };
    return statusMap[status] || 'secondary';
}

// 获取状态文本
function getStatusText(status) {
    const statusMap = {
        'running': '运行中',
        'stopped': '已停止',
        'starting': '启动中',
        'stopping': '停止中',
        'pending': '创建中',
        'terminated': '已销毁',
        'unknown': '未知'
    };
    return statusMap[status] || status;
}

// 获取操作系统图标
function getOsIcon(osType) {
    const iconMap = {
        'linux': 'fab fa-linux',
        'windows': 'fab fa-windows',
        'unknown': 'fas fa-desktop'
    };
    return iconMap[osType] || 'fas fa-desktop';
}

// 格式化日期时间
function formatDateTime(dateTime) {
    if (!dateTime) return '-';
    return new Date(dateTime).toLocaleString('zh-CN');
}

// 显示提示信息
function showAlert(message, type = 'info') {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    `;
    
    // 移除现有的提示
    $('.alert').remove();
    
    // 添加新的提示到页面顶部
    $('.container-fluid').prepend(alertHtml);
    
    // 3秒后自动消失
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 3000);
}
</script>
@endpush