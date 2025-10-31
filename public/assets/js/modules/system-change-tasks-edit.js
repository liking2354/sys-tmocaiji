/**
 * 系统变更任务编辑模块
 * 功能：任务编辑、服务器选择、模板选择、变量配置
 */

$(document).ready(function() {
    // 初始化已选择的服务器
    if (window.taskServerGroupId) {
        loadServers(window.taskServerGroupId, window.taskServerIds || []);
    }
    
    // 初始化变量配置
    loadVariables();
    
    // 服务器分组变化时加载服务器
    $('#server_group_id').change(function() {
        var groupId = $(this).val();
        if (groupId) {
            loadServers(groupId);
        } else {
            $('#servers-list').empty();
        }
    });
    
    // 全选服务器
    $('#select-all-servers').change(function() {
        $('.server-checkbox').prop('checked', $(this).prop('checked'));
    });
    
    // 模板选择变化时加载变量
    $('.template-checkbox').change(function() {
        loadVariables();
    });
});

/**
 * 加载服务器列表
 * @param {number} groupId - 服务器分组ID
 * @param {array} selectedIds - 已选择的服务器ID列表
 */
function loadServers(groupId, selectedIds = []) {
    $.get(window.getServersUrl, {
        server_group_id: groupId
    }).done(function(data) {
        var html = '';
        if (data.servers && data.servers.length > 0) {
            data.servers.forEach(function(server) {
                var checked = selectedIds.includes(server.id) ? 'checked' : '';
                html += '<div class="form-check form-check-inline mr-3">';
                html += '<input type="checkbox" class="form-check-input server-checkbox" ';
                html += 'id="server_' + server.id + '" name="server_ids[]" value="' + server.id + '" ' + checked + '>';
                html += '<label class="form-check-label" for="server_' + server.id + '">';
                html += server.name + ' (' + server.host + ')';
                html += '</label>';
                html += '</div>';
            });
        } else {
            html = '<p class="text-muted">该分组下没有可用的服务器</p>';
        }
        $('#servers-list').html(html);
    }).fail(function() {
        $('#servers-list').html('<p class="text-danger">加载服务器失败</p>');
    });
}

/**
 * 加载变量配置
 */
function loadVariables() {
    var selectedTemplates = [];
    $('.template-checkbox:checked').each(function() {
        selectedTemplates.push($(this).val());
    });
    
    if (selectedTemplates.length === 0) {
        $('#variables-section').hide();
        return;
    }
    
    $.post(window.getVariablesUrl, {
        _token: window.csrfToken,
        template_ids: selectedTemplates
    }).done(function(data) {
        if (data.variables && Object.keys(data.variables).length > 0) {
            var html = '';
            var existingValues = window.configVariables || {};
            
            Object.keys(data.variables).forEach(function(varName) {
                var variable = data.variables[varName];
                var value = existingValues[varName] || variable.default_value || '';
                
                html += '<div class="form-group row">';
                html += '<label class="col-sm-3 col-form-label">' + varName;
                if (variable.required) {
                    html += ' <span class="text-danger">*</span>';
                }
                html += '</label>';
                html += '<div class="col-sm-9">';
                html += '<input type="text" class="form-control" name="config_variables[' + varName + ']" ';
                html += 'value="' + value + '" placeholder="' + (variable.description || '') + '"';
                if (variable.required) {
                    html += ' required';
                }
                html += '>';
                if (variable.description) {
                    html += '<small class="form-text text-muted">' + variable.description + '</small>';
                }
                html += '</div>';
                html += '</div>';
            });
            
            $('#variables-container').html(html);
            $('#variables-section').show();
        } else {
            $('#variables-section').hide();
        }
    }).fail(function() {
        $('#variables-container').html('<p class="text-danger">加载变量配置失败</p>');
        $('#variables-section').show();
    });
}
