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
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#syncModal">
                            <i class="fas fa-sync"></i> 同步资源
                        </button>
                        <button type="button" class="btn btn-info" onclick="showStatistics()">
                            <i class="fas fa-chart-bar"></i> 统计信息
                        </button>
                        <button type="button" class="btn btn-warning" onclick="cleanupResources()">
                            <i class="fas fa-trash"></i> 清理过期
                        </button>
                    </div>
                </div>

                <!-- 搜索筛选 -->
                <div class="card-body">
                    <form id="searchForm" class="row g-3 mb-4">
                        <div class="col-md-2">
                            <label class="form-label">云平台</label>
                            <select name="platform_id" class="form-control">
                                <option value="">全部平台</option>
                                @foreach($platforms as $platform)
                                    <option value="{{ $platform->id }}" {{ ($filters['platform_id'] ?? '') == $platform->id ? 'selected' : '' }}>
                                        {{ $platform->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">资源分类</label>
                            <select name="resource_category" class="form-control" onchange="updateResourceTypes()">
                                <option value="">全部分类</option>
                                @foreach($resourceCategories as $category)
                                    <option value="{{ $category->item_code }}" {{ ($filters['resource_category'] ?? '') == $category->item_code ? 'selected' : '' }}>
                                        {{ $category->item_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">资源类型</label>
                            <select name="resource_type" class="form-control" id="resourceTypeSelect">
                                <option value="">全部类型</option>
                                @foreach($resourceTypes as $type)
                                    <option value="{{ $type->item_code }}" 
                                            data-category="{{ $type->parent ? $type->parent->item_code : '' }}"
                                            {{ ($filters['resource_type'] ?? '') == $type->item_code ? 'selected' : '' }}>
                                        {{ $type->item_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">状态</label>
                            <select name="status" class="form-control">
                                <option value="">全部状态</option>
                                <option value="running" {{ ($filters['status'] ?? '') == 'running' ? 'selected' : '' }}>运行中</option>
                                <option value="stopped" {{ ($filters['status'] ?? '') == 'stopped' ? 'selected' : '' }}>已停止</option>
                                <option value="unknown" {{ ($filters['status'] ?? '') == 'unknown' ? 'selected' : '' }}>未知</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">资源名称</label>
                            <input type="text" name="name" class="form-control" placeholder="搜索资源名称" value="{{ $filters['name'] ?? '' }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">资源ID</label>
                            <input type="text" name="resource_id" class="form-control" placeholder="搜索资源ID" value="{{ $filters['resource_id'] ?? '' }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> 搜索
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- 统计信息卡片 -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4>{{ $statistics['total'] }}</h4>
                                            <p class="mb-0">总资源数</p>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-server fa-2x"></i>
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
                                            <h4>{{ count($statistics['by_platform']) }}</h4>
                                            <p class="mb-0">接入平台</p>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-cloud fa-2x"></i>
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
                                            <h4>{{ count($statistics['by_type']) }}</h4>
                                            <p class="mb-0">资源类型</p>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-list fa-2x"></i>
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
                                            <h4>{{ count($statistics['by_region']) }}</h4>
                                            <p class="mb-0">覆盖区域</p>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-globe fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 资源列表 -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="selectAll"></th>
                                    <th>资源名称</th>
                                    <th>资源ID</th>
                                    <th>云平台</th>
                                    <th>资源类型</th>
                                    <th>状态</th>
                                    <th>区域</th>
                                    <th>最后同步</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($resources as $resource)
                                    <tr>
                                        <td><input type="checkbox" name="resource_ids[]" value="{{ $resource->id }}"></td>
                                        <td>{{ $resource->resource_name ?: '-' }}</td>
                                        <td><code>{{ $resource->resource_id }}</code></td>
                                        <td>
                                            <span class="badge bg-primary">{{ $resource->platform->name ?? '-' }}</span>
                                        </td>
                                        <td>{{ $resource->resource_type }}</td>
                                        <td>
                                            @switch($resource->status)
                                                @case('running')
                                                    <span class="badge bg-success">运行中</span>
                                                    @break
                                                @case('stopped')
                                                    <span class="badge bg-danger">已停止</span>
                                                    @break
                                                @default
                                                    <span class="badge bg-secondary">{{ $resource->status }}</span>
                                            @endswitch
                                        </td>
                                        <td>{{ $resource->region_name ?? '-' }}</td>
                                        <td>{{ $resource->last_sync_at ? $resource->last_sync_at->format('Y-m-d H:i') : '-' }}</td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="{{ route('cloud.resources.show', $resource) }}" class="btn btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <button type="button" class="btn btn-outline-danger" onclick="deleteResource({{ $resource->id }})">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted">暂无资源数据</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- 分页 -->
                    @if($resources->hasPages())
                        <div class="d-flex justify-content-center">
                            {{ $resources->appends($filters)->links() }}
                        </div>
                    @endif

                    <!-- 批量操作 -->
                    <div class="mt-3">
                        <button type="button" class="btn btn-danger" onclick="batchDelete()" id="batchDeleteBtn" style="display: none;">
                            <i class="fas fa-trash"></i> 批量删除
                        </button>
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
                <h5 class="modal-title">同步云资源</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="syncForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">选择云平台</label>
                        <select name="platform_id" class="form-control" required>
                            <option value="">请选择云平台</option>
                            @foreach($platforms as $platform)
                                <option value="{{ $platform->id }}">{{ $platform->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">资源分类 <span class="text-danger">*</span></label>
                        <select name="resource_category" class="form-control" required onchange="updateSyncResourceTypes()">
                            <option value="">请选择资源分类</option>
                            @foreach($resourceCategories as $category)
                                <option value="{{ $category->item_code }}">{{ $category->item_name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">必须选择一个资源分类以精确同步</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">资源类型 <span class="text-danger">*</span></label>
                        <div id="syncResourceTypeSelect" class="border rounded p-2" style="max-height: 200px; overflow-y: auto;">
                            @foreach($resourceTypes as $type)
                                <div class="form-check" data-category="{{ $type->parent ? $type->parent->item_code : '' }}">
                                    <input class="form-check-input" type="checkbox" 
                                           name="resource_types[]" 
                                           value="{{ $type->item_code }}" 
                                           id="type_{{ $type->id }}">
                                    <label class="form-check-label" for="type_{{ $type->id }}">
                                        {{ $type->parent ? $type->parent->item_name . ' - ' : '' }}{{ $type->item_name }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        <div class="form-text">必须选择至少一个资源类型进行同步</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary">开始同步</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

<!-- 引入执行进度组件 -->
@include('components.execution-progress')

@push('scripts')
<script>
$(document).ready(function() {
    // 全选/取消全选
    $('#selectAll').change(function() {
        $('input[name="resource_ids[]"]').prop('checked', this.checked);
        toggleBatchActions();
    });

    // 单选框变化
    $('input[name="resource_ids[]"]').change(function() {
        toggleBatchActions();
    });

    // 搜索表单提交
    $('#searchForm').submit(function(e) {
        e.preventDefault();
        window.location.href = '?' + $(this).serialize();
    });

    // 同步表单提交
    $('#syncForm').submit(function(e) {
        e.preventDefault();
        syncResources();
    });
});

// 切换批量操作按钮显示
function toggleBatchActions() {
    const checked = $('input[name="resource_ids[]"]:checked').length;
    if (checked > 0) {
        $('#batchDeleteBtn').show();
    } else {
        $('#batchDeleteBtn').hide();
    }
}

// 同步资源
function syncResources() {
    const formData = new FormData($('#syncForm')[0]);
    const platformId = formData.get('platform_id');
    const resourceCategory = formData.get('resource_category');
    const resourceTypes = formData.getAll('resource_types[]');
    
    if (!platformId) {
        alert('请选择云平台');
        return;
    }
    
    // 隐藏同步模态框
    $('#syncModal').modal('hide');
    
    // 准备同步步骤
    const steps = [
        '初始化同步环境',
        '验证云平台连接',
        '获取资源类型配置',
        '同步云资源数据',
        '更新本地数据库',
        '完成同步操作'
    ];
    
    // 初始化进度框
    if (window.executionProgressManager) {
        window.executionProgressManager.init('云资源同步', steps, function() {
            // 重试回调
            syncResources();
        });
        
        // 开始同步流程
        startSyncProcess(platformId, resourceCategory, resourceTypes);
    } else {
        // 降级到原有的同步方式
        fallbackSync(formData);
    }
}

// 开始同步流程
function startSyncProcess(platformId, resourceCategory, resourceTypes) {
    const progressManager = window.executionProgressManager;
    let currentStep = 0;
    
    // 步骤1: 初始化同步环境
    progressManager.startStep(currentStep, '准备同步参数');
    
    setTimeout(() => {
        progressManager.completeStep(currentStep, true, '同步环境初始化完成');
        currentStep++;
        
        // 步骤2: 验证云平台连接
        progressManager.startStep(currentStep, '正在验证云平台连接');
        
        // 验证连接
        $.ajax({
            url: '{{ route("cloud.resources.validate-connection") }}',
            method: 'POST',
            data: { platform_id: platformId },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    progressManager.completeStep(currentStep, true, '云平台连接验证成功');
                    currentStep++;
                    
                    // 继续下一步
                    getResourceConfig(platformId, resourceCategory, resourceTypes, currentStep);
                } else {
                    progressManager.completeStep(currentStep, false, '连接验证失败: ' + response.message);
                    progressManager.showResult(false, '同步失败', '云平台连接验证失败', response.message);
                }
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || '网络错误';
                progressManager.completeStep(currentStep, false, '连接验证失败: ' + message);
                progressManager.showResult(false, '同步失败', '云平台连接验证失败', message);
            }
        });
    }, 500);
}

// 获取资源配置
function getResourceConfig(platformId, resourceCategory, resourceTypes, currentStep) {
    const progressManager = window.executionProgressManager;
    
    // 步骤3: 获取资源类型配置
    progressManager.startStep(currentStep, '正在获取资源类型配置');
    
    $.ajax({
        url: '{{ route("cloud.resources.get-sync-config") }}',
        method: 'POST',
        data: { 
            platform_id: platformId,
            resource_category: resourceCategory,
            resource_types: resourceTypes
        },
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                const config = response.data;
                progressManager.completeStep(currentStep, true, `获取到 ${config.resource_count} 个资源类型配置`);
                currentStep++;
                
                // 继续同步数据
                syncResourceData(platformId, config, currentStep);
            } else {
                progressManager.completeStep(currentStep, false, '获取配置失败: ' + response.message);
                progressManager.showResult(false, '同步失败', '获取资源配置失败', response.message);
            }
        },
        error: function(xhr) {
            const message = xhr.responseJSON?.message || '网络错误';
            progressManager.completeStep(currentStep, false, '获取配置失败: ' + message);
            progressManager.showResult(false, '同步失败', '获取资源配置失败', message);
        }
    });
}

// 同步资源数据
function syncResourceData(platformId, config, currentStep) {
    const progressManager = window.executionProgressManager;
    
    // 步骤4: 同步云资源数据
    progressManager.startStep(currentStep, '正在同步云资源数据');
    
    // 创建同步任务
    $.ajax({
        url: '{{ route("cloud.resources.sync-with-progress") }}',
        method: 'POST',
        data: { 
            platform_id: platformId,
            config: config
        },
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                const taskId = response.data.task_id;
                // 开始轮询同步进度
                pollSyncProgress(taskId, currentStep);
            } else {
                progressManager.completeStep(currentStep, false, '启动同步失败: ' + response.message);
                progressManager.showResult(false, '同步失败', '启动同步任务失败', response.message);
            }
        },
        error: function(xhr) {
            const message = xhr.responseJSON?.message || '网络错误';
            progressManager.completeStep(currentStep, false, '启动同步失败: ' + message);
            progressManager.showResult(false, '同步失败', '启动同步任务失败', message);
        }
    });
}

// 轮询同步进度
function pollSyncProgress(taskId, currentStep) {
    const progressManager = window.executionProgressManager;
    
    const pollInterval = setInterval(() => {
        $.ajax({
            url: '{{ route("cloud.resources.sync-progress") }}',
            method: 'POST',
            data: { task_id: taskId },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    const progress = response.data;
                    
                    // 更新步骤进度
                    progressManager.updateStepProgress(
                        currentStep, 
                        `已处理 ${progress.processed}/${progress.total} 个资源`,
                        `成功: ${progress.success}, 失败: ${progress.failed}`
                    );
                    
                    if (progress.completed) {
                        clearInterval(pollInterval);
                        
                        if (progress.success > 0 || progress.failed === 0) {
                            progressManager.completeStep(currentStep, true, `同步完成，成功: ${progress.success}, 失败: ${progress.failed}`);
                            
                            // 继续下一步
                            updateLocalDatabase(progress, currentStep + 1);
                        } else {
                            progressManager.completeStep(currentStep, false, `同步失败，所有资源都同步失败`);
                            progressManager.showResult(false, '同步失败', '所有资源同步失败', progress.error_details);
                        }
                    }
                }
            },
            error: function() {
                clearInterval(pollInterval);
                progressManager.completeStep(currentStep, false, '获取同步进度失败');
                progressManager.showResult(false, '同步失败', '无法获取同步进度');
            }
        });
    }, 2000); // 每2秒轮询一次
}

// 更新本地数据库
function updateLocalDatabase(syncResult, currentStep) {
    const progressManager = window.executionProgressManager;
    
    // 步骤5: 更新本地数据库
    progressManager.startStep(currentStep, '正在更新本地数据库');
    
    setTimeout(() => {
        progressManager.completeStep(currentStep, true, '本地数据库更新完成');
        
        // 步骤6: 完成同步操作
        progressManager.startStep(currentStep + 1, '正在完成同步操作');
        
        setTimeout(() => {
            progressManager.completeStep(currentStep + 1, true, '同步操作完成');
            
            // 显示最终结果
            progressManager.showResult(
                true, 
                '同步完成', 
                `资源同步成功完成！成功: ${syncResult.success}, 失败: ${syncResult.failed}`,
                syncResult.error_details || ''
            );
            
            // 3秒后自动刷新页面
            setTimeout(() => {
                location.reload();
            }, 3000);
        }, 1000);
    }, 1000);
}

// 降级同步方式（当进度组件不可用时）
function fallbackSync(formData) {
    $.ajax({
        url: '{{ route("cloud.resources.sync") }}',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        beforeSend: function() {
            $('#syncForm button[type="submit"]').prop('disabled', true).text('同步中...');
        },
        success: function(response) {
            if (response.success) {
                alert('同步完成！成功: ' + response.data.success + ', 失败: ' + response.data.failed);
                location.reload();
            } else {
                alert('同步失败: ' + response.message);
            }
        },
        error: function(xhr) {
            var message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : '网络错误';
            alert('同步失败: ' + message);
        },
        complete: function() {
            $('#syncForm button[type="submit"]').prop('disabled', false).text('开始同步');
        }
    });
}

// 显示统计信息
function showStatistics() {
    // 这里可以实现统计信息的详细展示
    alert('统计信息功能待完善');
}

// 清理过期资源
function cleanupResources() {
    if (confirm('确定要清理30天前的过期资源数据吗？')) {
        $.ajax({
            url: '{{ route("cloud.resources.cleanup") }}',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: { days: 30 },
            success: function(response) {
                if (response.success) {
                    alert(response.message);
                    location.reload();
                } else {
                    alert('清理失败: ' + response.message);
                }
            },
            error: function(xhr) {
                var message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : '网络错误';
                alert('清理失败: ' + message);
            }
        });
    }
}

// 删除单个资源
function deleteResource(id) {
    if (confirm('确定要删除这个资源记录吗？')) {
        batchDelete([id]);
    }
}

// 批量删除
function batchDelete(ids = null) {
    if (!ids) {
        ids = $('input[name="resource_ids[]"]:checked').map(function() {
            return $(this).val();
        }).get();
    }

    if (ids.length === 0) {
        alert('请选择要删除的资源');
        return;
    }

    if (confirm('确定要删除选中的 ' + ids.length + ' 个资源记录吗？')) {
        $.ajax({
            url: '{{ route("cloud.resources.batch-delete") }}',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: { resource_ids: ids },
            success: function(response) {
                if (response.success) {
                    alert(response.message);
                    location.reload();
                } else {
                    alert('删除失败: ' + response.message);
                }
            },
            error: function(xhr) {
                var message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : '网络错误';
                alert('删除失败: ' + message);
            }
        });
    }
}

// 更新资源类型下拉框
function updateResourceTypes() {
    const categorySelect = $('select[name="resource_category"]');
    const typeSelect = $('#resourceTypeSelect');
    const selectedCategory = categorySelect.val();
    
    // 显示/隐藏资源类型选项
    typeSelect.find('option').each(function() {
        const option = $(this);
        const category = option.data('category');
        
        if (!selectedCategory || !category || category === selectedCategory) {
            option.show();
        } else {
            option.hide();
        }
    });
    
    // 重置资源类型选择
    if (selectedCategory) {
        typeSelect.val('');
    }
}

// 更新同步模态框中的资源类型选择
function updateSyncResourceTypes() {
    const categorySelect = $('#syncModal select[name="resource_category"]');
    const typeContainer = $('#syncResourceTypeSelect');
    const selectedCategory = categorySelect.val();
    
    console.log('更新资源类型选择', {
        selectedCategory: selectedCategory,
        checkboxCount: typeContainer.find('.form-check').length
    });
    
    // 显示/隐藏资源类型选项
    typeContainer.find('.form-check').each(function() {
        const checkDiv = $(this);
        const category = checkDiv.data('category');
        const checkbox = checkDiv.find('input[type="checkbox"]');
        
        console.log('处理选项', {
            text: checkDiv.find('label').text(),
            value: checkbox.val(),
            category: category,
            shouldShow: !selectedCategory || !category || category === selectedCategory
        });
        
        if (!selectedCategory || !category || category === selectedCategory) {
            checkDiv.show();
        } else {
            checkDiv.hide();
            // 隐藏时取消选中
            checkbox.prop('checked', false);
        }
    });
}
</script>
@endpush