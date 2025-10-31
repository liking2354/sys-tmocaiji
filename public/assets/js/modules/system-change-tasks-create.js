/**
 * 系统变更任务创建模块
 * 功能：服务器选择、模板选择、变量配置
 */

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
        url: window.getVariablesUrl || '{{ route("system-change.templates.get-variables") }}',
        method: 'POST',
        data: {
            _token: window.csrfToken || $('meta[name="csrf-token"]').attr('content'),
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
