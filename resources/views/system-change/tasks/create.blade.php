@extends('layouts.app')

@section('title', '创建系统变更任务')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-plus mr-2"></i>
                        创建系统变更任务
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
                                           id="name" name="name" value="{{ old('name') }}" required>
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

                        <!-- 服务器选择 -->
                        <div class="form-group">
                            <label class="required">选择服务器</label>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="card-title mb-0">服务器分组</h6>
                                        </div>
                                        <div class="card-body p-2" style="max-height: 300px; overflow-y: auto;">
                                            @foreach($serverGroups as $group)
                                            <div class="form-check">
                                                <input class="form-check-input server-group-checkbox" 
                                                       type="checkbox" 
                                                       id="group_{{ $group->id }}"
                                                       data-group-id="{{ $group->id }}"
                                                       {{ in_array($group->id, old('server_group_ids', [])) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="group_{{ $group->id }}">
                                                    {{ $group->name }} 
                                                    <small class="text-muted">({{ $group->servers_count }} 台)</small>
                                                </label>
                                            </div>
                                            @endforeach
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
                                        <div class="card-body p-2" style="max-height: 300px; overflow-y: auto;">
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
                            @error('server_ids')
                                <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- 模板选择 -->
                        <div class="form-group">
                            <label class="required">选择配置模板</label>
                            <div class="row">
                                @foreach($templates as $template)
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="card template-card {{ in_array($template->id, old('template_ids', [])) ? 'border-primary' : '' }}">
                                        <div class="card-body p-3">
                                            <div class="form-check">
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
                                                        <br><small class="text-muted">{{ Str::limit($template->description, 60) }}</small>
                                                    @endif
                                                    <br><small class="text-info">{{ $template->config_items_count }} 个配置项</small>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
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
.template-card {
    cursor: pointer;
    transition: all 0.2s;
}
.template-card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
.template-card.border-primary {
    background-color: #f8f9ff;
}
.server-item {
    padding: 0.25rem 0;
    border-bottom: 1px solid #f8f9fa;
}
.server-item:last-child {
    border-bottom: none;
}
.variable-item {
    border: 1px solid #dee2e6;
    border-radius: 0.25rem;
    padding: 1rem;
    margin-bottom: 1rem;
    background-color: #f8f9fa;
}
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    // 服务器分组选择事件
    $('.server-group-checkbox').change(function() {
        loadServers();
    });
    
    // 模板选择事件
    $('.template-checkbox').change(function() {
        updateTemplateCard(this);
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
    
    if (serverGroupId && serverGroupName) {
        // 预选指定的服务器分组
        $(`#group_${serverGroupId}`).prop('checked', true);
        
        // 设置任务名称
        const currentName = $('#name').val();
        if (!currentName) {
            $('#name').val(`${serverGroupName} - 系统变更任务`);
        }
        
        // 加载该分组的服务器
        loadServers();
        
        // 显示提示信息
        if (typeof toastr !== 'undefined') {
            toastr.info(`已预选服务器分组: ${serverGroupName}`);
        }
    }
    
    // 初始化
    if ($('.server-group-checkbox:checked').length > 0) {
        loadServers();
    }
    
    if ($('.template-checkbox:checked').length > 0) {
        loadVariables();
    }
});

function updateTemplateCard(checkbox) {
    const card = $(checkbox).closest('.template-card');
    if (checkbox.checked) {
        card.addClass('border-primary');
    } else {
        card.removeClass('border-primary');
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
    
    $.get('/system-change/server-groups/0/servers', {
        group_ids: selectedGroups
    })
    .done(function(response) {
        if (response.servers && response.servers.length > 0) {
            let html = '';
            response.servers.forEach(server => {
                html += `
                    <div class="server-item">
                        <div class="form-check">
                            <input class="form-check-input server-checkbox" 
                                   type="checkbox" 
                                   id="server_${server.id}"
                                   name="server_ids[]"
                                   value="${server.id}">
                            <label class="form-check-label" for="server_${server.id}">
                                <strong>${server.name}</strong>
                                <br><small class="text-muted">${server.hostname}:${server.port || 22}</small>
                                <span class="badge badge-${server.status === 'online' ? 'success' : 'secondary'} badge-sm ml-2">
                                    ${server.status === 'online' ? '在线' : '离线'}
                                </span>
                            </label>
                        </div>
                    </div>
                `;
            });
            $('#servers-container').html(html);
        } else {
            $('#servers-container').html(`
                <div class="text-muted text-center">
                    <i class="fas fa-exclamation-circle fa-2x mb-2"></i><br>
                    所选分组中没有服务器
                </div>
            `);
        }
    })
    .fail(function() {
        $('#servers-container').html(`
            <div class="text-danger text-center">
                <i class="fas fa-exclamation-triangle fa-2x mb-2"></i><br>
                加载服务器失败
            </div>
        `);
    });
}

function loadVariables() {
    const selectedTemplates = $('.template-checkbox:checked').map(function() {
        return $(this).val();
    }).get();
    
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
    
    $.post('/system-change/templates/variables', {
        _token: $('meta[name="csrf-token"]').attr('content'),
        template_ids: selectedTemplates
    })
    .done(function(response) {
        if (response.variables && Object.keys(response.variables).length > 0) {
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
        } else {
            $('#variables-container').html(`
                <div class="text-info text-center">
                    <i class="fas fa-info-circle fa-2x mb-2"></i><br>
                    所选模板无需配置变量
                </div>
            `);
        }
    })
    .fail(function() {
        $('#variables-container').html(`
            <div class="text-danger text-center">
                <i class="fas fa-exclamation-triangle fa-2x mb-2"></i><br>
                加载变量失败
            </div>
        `);
    });
}
</script>
@endpush