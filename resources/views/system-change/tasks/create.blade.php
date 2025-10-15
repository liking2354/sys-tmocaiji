@extends('layouts.app')

@section('title', '创建配置任务')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-plus mr-2"></i>
                        创建配置任务
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('system-change.tasks.index') }}" class="btn btn-default btn-sm">
                            <i class="fas fa-arrow-left mr-1"></i>
                            返回列表
                        </a>
                    </div>
                </div>

                <form method="POST" action="{{ route('system-change.tasks.store') }}">
                    @csrf
                    <div class="card-body">
                        <!-- 基本信息 -->
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="name" class="required">任务名称</label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                           id="name" name="name" value="{{ old('name', $defaultTaskName ?? '') }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="execution_order">执行方式</label>
                                    <select class="form-control @error('execution_order') is-invalid @enderror" 
                                            id="execution_order" name="execution_order">
                                        <option value="sequential" {{ old('execution_order', 'sequential') === 'sequential' ? 'selected' : '' }}>
                                            顺序执行
                                        </option>
                                        <option value="parallel" {{ old('execution_order') === 'parallel' ? 'selected' : '' }}>
                                            并行执行
                                        </option>
                                    </select>
                                    @error('execution_order')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description">任务描述</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="3">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- 隐藏字段 -->
                        <input type="hidden" name="server_group_id" id="server_group_id" value="{{ $selectedServerGroupId ?? '' }}">
                        
                        <!-- 服务器选择 -->
                        <div class="form-group">
                            <label class="required">选择服务器</label>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="card-title mb-0">服务器分组</h6>
                                        </div>
                                        <div class="card-body p-2">
                                            <div class="checkbox-container">
                                                <div class="row">
                                                    @foreach($serverGroups as $group)
                                                    <div class="col-md-6 mb-2">
                                                        <div class="form-check">
                                                            <input class="form-check-input server-group-checkbox" 
                                                                   type="checkbox" 
                                                                   id="group_{{ $group->id }}"
                                                                   data-group-id="{{ $group->id }}"
                                                                   data-group-name="{{ $group->name }}"
                                                                   {{ (isset($selectedServerGroupId) && $selectedServerGroupId == $group->id) || in_array($group->id, old('server_group_ids', [])) ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="group_{{ $group->id }}">
                                                                <strong>{{ $group->name }}</strong>
                                                                <br><small class="text-muted">({{ $group->servers_count ?? $group->servers->count() }} 台)</small>
                                                            </label>
                                                        </div>
                                                    </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="card-title mb-0">选择具体服务器</h6>
                                            <div class="card-tools">
                                                <button type="button" class="btn btn-tool text-primary" id="select-all-servers">
                                                    <i class="fas fa-check-square"></i> 全选
                                                </button>
                                                <button type="button" class="btn btn-tool text-secondary" id="deselect-all-servers">
                                                    <i class="fas fa-square"></i> 取消全选
                                                </button>
                                            </div>
                                        </div>
                                        <div class="card-body p-2">
                                            <div class="checkbox-container">
                                                <div id="servers-container">
                                                    <div class="text-muted text-center">
                                                        <i class="fas fa-server fa-2x mb-2"></i><br>
                                                        请先选择服务器分组
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @error('server_ids')
                                <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- 模板选择 -->
                        <div class="form-group">
                            <label class="required">选择配置模板</label>
                            <div class="card">
                                <div class="card-body p-2">
                                    <div class="template-container">
                                        <div class="row">
                                            @foreach($templates as $template)
                                            <div class="col-md-6 mb-2">
                                                <div class="form-check template-item">
                                                    <input class="form-check-input template-checkbox" 
                                                           type="checkbox" 
                                                           id="template_{{ $template->id }}"
                                                           name="template_ids[]"
                                                           value="{{ $template->id }}"
                                                           data-template-id="{{ $template->id }}"
                                                           {{ in_array($template->id, old('template_ids', [])) ? 'checked' : '' }}>
                                                    <label class="form-check-label w-100" for="template_{{ $template->id }}">
                                                        <strong>{{ $template->name }}</strong>
                                                        @if($template->description)
                                                            <br><small class="text-muted">{{ Str::limit($template->description, 50) }}</small>
                                                        @endif
                                                        <br><small class="text-info">
                                                            @if(isset($template->template_variables) && is_array($template->template_variables))
                                                                {{ count($template->template_variables) }} 个变量
                                                            @elseif(isset($template->config_items_count))
                                                                {{ $template->config_items_count }} 个配置项
                                                            @else
                                                                配置模板
                                                            @endif
                                                        </small>
                                                    </label>
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @error('template_ids')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- 变量配置 -->
                        <div class="form-group">
                            <label>配置变量</label>
                            <div id="variables-container">
                                <div class="text-muted text-center">
                                    <i class="fas fa-cogs fa-2x mb-2"></i><br>
                                    选择模板后将显示需要配置的变量
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i>
                            创建任务
                        </button>
                        <a href="{{ route('system-change.tasks.index') }}" class="btn btn-default ml-2">
                            <i class="fas fa-times mr-1"></i>
                            取消
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.required::after {
    content: " *";
    color: red;
}

/* 通用固定高度容器样式 */
.checkbox-container {
    max-height: 150px; /* 约5行的高度 */
    overflow-y: auto;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 10px;
    background-color: #f9f9f9;
}

.template-container {
    max-height: 120px; /* 约2行的高度 */
    overflow-y: auto;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 10px;
    background-color: #f9f9f9;
}

/* 服务器分组和服务器项样式 */
.checkbox-container .form-check {
    border: 1px solid #e9ecef;
    border-radius: 0.25rem;
    padding: 0.5rem;
    background-color: white;
    transition: all 0.2s;
    margin-bottom: 0.5rem;
}

.checkbox-container .form-check:hover {
    background-color: #f8f9fa;
    border-color: #007bff;
}

.checkbox-container .form-check-input:checked ~ .form-check-label {
    color: #007bff;
    font-weight: bold;
}

/* 模板项样式 */
.template-item {
    border: 1px solid #e9ecef;
    border-radius: 0.25rem;
    padding: 0.75rem;
    background-color: white;
    transition: all 0.2s;
    margin-bottom: 0.5rem;
    cursor: pointer;
}

.template-item:hover {
    background-color: #f8f9fa;
    border-color: #007bff;
    box-shadow: 0 2px 4px rgba(0,123,255,0.1);
}

.template-item .form-check-input:checked ~ .form-check-label {
    color: #007bff;
    font-weight: bold;
}

.template-item.selected {
    background-color: #f8f9ff;
    border-color: #007bff;
    box-shadow: 0 2px 4px rgba(0,123,255,0.2);
}

/* 服务器项特殊样式 */
.server-item {
    padding: 0.5rem;
    border: 1px solid #e9ecef;
    border-radius: 0.25rem;
    background-color: white;
    margin-bottom: 0.5rem;
    transition: all 0.2s;
}

.server-item:hover {
    background-color: #f8f9fa;
    border-color: #007bff;
}

.server-item .form-check-input:checked ~ .form-check-label {
    color: #007bff;
    font-weight: bold;
}

.server-label {
    font-size: 0.875rem;
    line-height: 1.2;
}

/* 变量配置样式 */
.variable-item {
    border: 1px solid #dee2e6;
    border-radius: 0.25rem;
    padding: 1rem;
    margin-bottom: 1rem;
    background-color: #f8f9fa;
    transition: all 0.2s;
}

.variable-item:hover {
    background-color: #e9ecef;
    border-color: #007bff;
}

/* 滚动条样式 */
.checkbox-container::-webkit-scrollbar,
.template-container::-webkit-scrollbar {
    width: 6px;
}

.checkbox-container::-webkit-scrollbar-track,
.template-container::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.checkbox-container::-webkit-scrollbar-thumb,
.template-container::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.checkbox-container::-webkit-scrollbar-thumb:hover,
.template-container::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* 响应式调整 */
@media (max-width: 768px) {
    .checkbox-container {
        max-height: 120px;
    }
    
    .template-container {
        max-height: 100px;
    }
    
    .checkbox-container .form-check,
    .template-item,
    .server-item {
        margin-bottom: 0.25rem;
        padding: 0.4rem;
    }
}

/* 两列布局优化 */
.checkbox-container .row,
.template-container .row {
    margin-left: -5px;
    margin-right: -5px;
}

.checkbox-container .col-md-6,
.template-container .col-md-6 {
    padding-left: 5px;
    padding-right: 5px;
}
</style>
@endpush

@section('scripts')
<script>
$(document).ready(function() {
    // 服务器分组选择事件
    $('.server-group-checkbox').change(function() {
        // 只允许选择一个分组
        if (this.checked) {
            $('.server-group-checkbox').not(this).prop('checked', false);
            $('#server_group_id').val($(this).data('group-id'));
        } else {
            $('#server_group_id').val('');
        }
        loadServers();
    });
    
    // 模板选择事件
    $('.template-checkbox').change(function() {
        updateTemplateItem(this);
        loadVariables();
    });
    
    // 全选/取消全选服务器
    $('#select-all-servers').click(function() {
        $('#servers-container .server-checkbox').prop('checked', true);
    });
    
    $('#deselect-all-servers').click(function() {
        $('#servers-container .server-checkbox').prop('checked', false);
    });
    

    
    // 处理URL参数，预选服务器分组
    const urlParams = new URLSearchParams(window.location.search);
    const serverGroupId = urlParams.get('server_group_id');
    const serverGroupName = urlParams.get('server_group_name');
    
    if (serverGroupId) {
        // 预选指定的服务器分组
        $(`#group_${serverGroupId}`).prop('checked', true);
        
        // 设置隐藏字段
        $('#server_group_id').val(serverGroupId);
        
        // 加载该分组的服务器
        loadServers();
        
        // 显示提示信息
        if (typeof toastr !== 'undefined' && serverGroupName) {
            toastr.info(`已预选服务器分组: ${serverGroupName}`);
        }
    }
    
    // 初始化
    if ($('.server-group-checkbox:checked').length > 0) {
        loadServers();
    }
    
    if ($('.template-checkbox:checked').length > 0) {
        // 更新已选中模板的样式
        $('.template-checkbox:checked').each(function() {
            updateTemplateItem(this);
        });
        loadVariables();
    }
});

function updateTemplateItem(checkbox) {
    const item = $(checkbox).closest('.template-item');
    if (checkbox.checked) {
        item.addClass('selected');
    } else {
        item.removeClass('selected');
    }
}

function loadServers() {
    const selectedGroups = $('.server-group-checkbox:checked').map(function() {
        return $(this).data('group-id');
    }).get();
    

    
    if (selectedGroups.length === 0) {
        $('#servers-container').html(`
            <div class="text-muted text-center">
                <i class="fas fa-server fa-2x mb-2"></i><br>
                请先选择服务器分组
            </div>
        `);
        return;
    }
    
    $('#servers-container').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> 加载中...</div>');
    
    const url = '/system-change/server-groups/servers';
    const params = { group_ids: selectedGroups };
    

    
    $.get(url, params)
    .done(function(response) {

        
        if (response.servers && response.servers.length > 0) {
            let html = '<div class="row">';
            response.servers.forEach((server, index) => {
                html += `
                    <div class="col-md-6 mb-2">
                        <div class="form-check">
                            <input class="form-check-input server-checkbox" 
                                   type="checkbox" 
                                   id="server_${server.id}"
                                   name="server_ids[]"
                                   value="${server.id}">
                            <label class="form-check-label" for="server_${server.id}">
                                <strong>${server.name}</strong>
                                <br><small class="text-muted">${server.ip}:${server.port || 22}</small>
                                <span class="badge badge-${server.status == 1 ? 'success' : 'secondary'} badge-sm ml-1">
                                    ${server.status == 1 ? '在线' : '离线'}
                                </span>
                            </label>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            $('#servers-container').html(html);
        } else {
            $('#servers-container').html(`
                <div class="text-muted text-center">
                    <i class="fas fa-exclamation-circle fa-2x mb-2"></i><br>
                    所选分组中没有服务器<br>
                    <small>响应数据: ${JSON.stringify(response)}</small>
                </div>
            `);
        }
    })
    .fail(function(xhr, status, error) {

        
        $('#servers-container').html(`
            <div class="text-danger text-center">
                <i class="fas fa-exclamation-triangle fa-2x mb-2"></i><br>
                加载服务器失败<br>
                <small>错误: ${xhr.status} - ${xhr.statusText}</small><br>
                <small>响应: ${xhr.responseText}</small>
            </div>
        `);
    });
}

function loadVariables() {
    const selectedTemplates = $('.template-checkbox:checked').map(function() {
        return $(this).val();
    }).get();
    
    console.log('加载变量，选中的模板:', selectedTemplates);
    
    if (selectedTemplates.length === 0) {
        $('#variables-container').html(`
            <div class="text-muted text-center">
                <i class="fas fa-cogs fa-2x mb-2"></i><br>
                选择模板后将显示需要配置的变量
            </div>
        `);
        return;
    }
    
    $('#variables-container').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> 加载变量...</div>');
    
    // 修改请求URL和参数
    $.ajax({
        url: '{{ route("system-change.templates.get-variables") }}',
        method: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content'),
            template_ids: selectedTemplates
        },
        dataType: 'json'
    })
    .done(function(response) {
        console.log('变量加载响应:', response);
        
        if (response.success && response.variables && Object.keys(response.variables).length > 0) {
            let html = '<div class="row">';
            Object.entries(response.variables).forEach(([key, info]) => {
                html += `
                    <div class="col-md-6 mb-3">
                        <div class="variable-item">
                            <label for="var_${key}" class="font-weight-bold">${key}</label>
                            ${info.description ? `<small class="text-muted d-block mb-2">${info.description}</small>` : ''}
                            <input type="text" 
                                   class="form-control" 
                                   id="var_${key}" 
                                   name="config_variables[${key}]" 
                                   placeholder="请输入${key}的值"
                                   value="${info.default_value || ''}"
                                   ${info.required ? 'required' : ''}>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            $('#variables-container').html(html);
        } else if (response.success) {
            $('#variables-container').html(`
                <div class="text-info text-center">
                    <i class="fas fa-info-circle fa-2x mb-2"></i><br>
                    所选模板无需配置变量
                </div>
            `);
        } else {
            $('#variables-container').html(`
                <div class="text-warning text-center">
                    <i class="fas fa-exclamation-triangle fa-2x mb-2"></i><br>
                    ${response.message || '加载变量时出现问题'}
                </div>
            `);
        }
    })
    .fail(function(xhr, status, error) {
        console.error('变量加载失败:', xhr.responseText);
        $('#variables-container').html(`
            <div class="text-danger text-center">
                <i class="fas fa-exclamation-triangle fa-2x mb-2"></i><br>
                加载变量失败<br>
                <small>错误: ${xhr.status} - ${error}</small><br>
                <small>响应: ${xhr.responseText}</small>
            </div>
        `);
    });
}
</script>
@endsection