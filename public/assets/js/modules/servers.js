/**
 * 服务器管理模块
 * 处理服务器列表页面的所有交互逻辑
 */

// 下载功能通用函数
function downloadServers(format, scope) {
    var serverIds = [];
    var fileName = '';
    
    // 根据scope确定要下载的服务器ID
    if (scope === 'selected') {
        // 已勾选的数据
        serverIds = $('.server-checkbox:checked').map(function() {
            return $(this).val();
        }).get();
        
        if (serverIds.length === 0) {
            alert('请至少选择一台服务器');
            return false;
        }
        fileName = '服务器数据_已勾选_' + serverIds.length + '台';
    } else if (scope === 'currentPage') {
        // 当前页数据
        serverIds = $('.server-checkbox').map(function() {
            return $(this).val();
        }).get();
        
        if (serverIds.length === 0) {
            alert('当前页没有服务器数据');
            return false;
        }
        fileName = '服务器数据_当前页_' + serverIds.length + '台';
    } else if (scope === 'allFiltered') {
        // 全部查询数据 - 需要调用后端API获取所有符合条件的数据
        downloadAllFiltered(format);
        return;
    } else {
        // 默认为已勾选
        serverIds = $('.server-checkbox:checked').map(function() {
            return $(this).val();
        }).get();
        
        if (serverIds.length === 0) {
            alert('请至少选择一台服务器');
            return false;
        }
        fileName = '服务器数据_已勾选_' + serverIds.length + '台';
    }
    
    // 创建临时表单
    var tempForm = $('<form>', {
        action: window.serversDownloadRoute,
        method: 'POST',
        style: 'display: none;',
        target: '_blank'
    });
    
    // 添加CSRF令牌
    tempForm.append('<input type="hidden" name="_token" value="' + window.csrfToken + '">');
    
    // 添加格式参数
    tempForm.append('<input type="hidden" name="format" value="' + format + '">');
    
    // 添加服务器ID
    serverIds.forEach(function(serverId) {
        tempForm.append('<input type="hidden" name="server_ids[]" value="' + serverId + '">');
    });
    
    // 添加到body并提交
    $('body').append(tempForm);
    tempForm.submit();
    
    // 稍后移除临时表单
    setTimeout(function() {
        tempForm.remove();
    }, 1000);
}

// 下载全部查询数据
function downloadAllFiltered(format) {
    // 获取当前搜索条件
    var search = $('#search').val() || '';
    var groupId = $('#group_id').val() || '';
    var status = $('#status').val() || '';
    
    // 创建临时表单
    var tempForm = $('<form>', {
        action: window.serversDownloadAllFilteredRoute,
        method: 'POST',
        style: 'display: none;',
        target: '_blank'
    });
    
    // 添加CSRF令牌
    tempForm.append('<input type="hidden" name="_token" value="' + window.csrfToken + '">');
    
    // 添加格式参数
    tempForm.append('<input type="hidden" name="format" value="' + format + '">');
    
    // 添加搜索条件
    if (search) tempForm.append('<input type="hidden" name="search" value="' + search + '">');
    if (groupId) tempForm.append('<input type="hidden" name="group_id" value="' + groupId + '">');
    if (status) tempForm.append('<input type="hidden" name="status" value="' + status + '">');
    
    // 添加到body并提交
    $('body').append(tempForm);
    tempForm.submit();
    
    // 稍后移除临时表单
    setTimeout(function() {
        tempForm.remove();
    }, 1000);
}

// 显示格式选择对话框
function showDownloadFormatDialog(scope) {
    var scopeText = {
        'selected': '已勾选的数据',
        'currentPage': '当前页数据',
        'allFiltered': '全部查询数据'
    };
    
    var html = '<div class="alert alert-info mb-3">' +
        '<i class="fas fa-info-circle"></i> 您将下载：<strong>' + scopeText[scope] + '</strong>' +
        '</div>' +
        '<div class="btn-group btn-block" role="group">' +
        '<button type="button" class="btn btn-outline-primary" onclick="downloadServers(\'xlsx\', \'' + scope + '\'); $(\'#formatDialog\').modal(\'hide\');">' +
        '<i class="fas fa-file-excel"></i> Excel (.xlsx)' +
        '</button>' +
        '<button type="button" class="btn btn-outline-primary" onclick="downloadServers(\'csv\', \'' + scope + '\'); $(\'#formatDialog\').modal(\'hide\');">' +
        '<i class="fas fa-file-csv"></i> CSV (.csv)' +
        '</button>' +
        '</div>';
    
    // 如果对话框不存在，创建它
    if ($('#formatDialog').length === 0) {
        $('body').append(
            '<div class="modal fade" id="formatDialog" tabindex="-1" role="dialog">' +
            '<div class="modal-dialog" role="document">' +
            '<div class="modal-content">' +
            '<div class="modal-header">' +
            '<h5 class="modal-title">选择下载格式</h5>' +
            '<button type="button" class="close" data-dismiss="modal" aria-label="Close">' +
            '<span aria-hidden="true">&times;</span>' +
            '</button>' +
            '</div>' +
            '<div class="modal-body" id="formatDialogContent"></div>' +
            '</div>' +
            '</div>' +
            '</div>'
        );
    }
    
    $('#formatDialogContent').html(html);
    $('#formatDialog').modal('show');
}

// 更新按钮状态的函数
function updateServerButtonStates() {
    var checkedCount = $('.server-checkbox:checked').length;
    $('#downloadBtn').prop('disabled', checkedCount === 0);
    $('#batchCollectionBtn').prop('disabled', checkedCount === 0);
    $('#batchModifyComponentsBtn').prop('disabled', checkedCount === 0);
    
    // 更新全选框状态
    var allChecked = checkedCount === $('.server-checkbox').length;
    $('#selectAll').prop('checked', allChecked && checkedCount > 0);
}

// 加载所有采集组件
function loadAllComponents() {
    $('#componentsContainer').html('<div class="col-12"><div class="text-muted"><i class="fas fa-spinner fa-spin"></i> 正在加载采集组件...</div></div>');
    
    $.ajax({
        url: window.apiCollectorsAllRoute,
        type: 'GET',
        headers: {
            'X-CSRF-TOKEN': window.csrfToken,
            'Accept': 'application/json'
        },
        success: function(response) {
            if (response.success && response.data.length > 0) {
                var html = '';
                response.data.forEach(function(collector) {
                    html += '<div class="col-md-6 mb-2">';
                    html += '<div class="form-check">';
                    html += '<input class="form-check-input component-checkbox" type="checkbox" name="collector_ids[]" value="' + collector.id + '" id="component_' + collector.id + '">';
                    html += '<label class="form-check-label" for="component_' + collector.id + '">';
                    html += '<strong>' + collector.name + '</strong> (' + collector.code + ')';
                    if (collector.description) {
                        html += '<br><small class="text-muted">' + collector.description + '</small>';
                    }
                    html += '</label>';
                    html += '</div>';
                    html += '</div>';
                });
                $('#componentsContainer').html(html);
                
                // 监听组件选择变化
                $('.component-checkbox').change(function() {
                    updateSelectAllComponents();
                });
                
                // 全选/取消全选功能
                $('#selectAllComponents').change(function() {
                    $('.component-checkbox').prop('checked', $(this).prop('checked'));
                });
                
            } else {
                $('#componentsContainer').html('<div class="col-12"><div class="alert alert-warning">没有可用的采集组件</div></div>');
            }
        },
        error: function(xhr) {
            $('#componentsContainer').html('<div class="col-12"><div class="alert alert-danger">加载采集组件失败：' + xhr.responseText + '</div></div>');
        }
    });
}

// 更新全选组件复选框状态
function updateSelectAllComponents() {
    var totalComponents = $('.component-checkbox').length;
    var checkedComponents = $('.component-checkbox:checked').length;
    $('#selectAllComponents').prop('checked', totalComponents > 0 && checkedComponents === totalComponents);
}

// 加载共同的采集组件
function loadCommonCollectors(serverIds) {
    $('#collectorsList').html('<div class="text-muted"><i class="fas fa-spinner fa-spin"></i> 正在加载共同的采集组件...</div>');
    $('#submitBatchCollection').prop('disabled', true);
    
    $.ajax({
        url: window.apiServersCommonCollectorsRoute,
        type: 'POST',
        headers: {
            'X-CSRF-TOKEN': window.csrfToken,
            'Accept': 'application/json'
        },
        data: {
            server_ids: serverIds
        },
        success: function(response) {
            if (response.success && response.data.length > 0) {
                var html = '';
                response.data.forEach(function(collector) {
                    html += '<div class="form-check">';
                    html += '<input class="form-check-input collector-checkbox" type="checkbox" name="collector_ids[]" value="' + collector.id + '" id="collector_' + collector.id + '">';
                    html += '<label class="form-check-label" for="collector_' + collector.id + '">';
                    html += collector.name + ' (' + collector.code + ')';
                    if (collector.description) {
                        html += '<br><small class="text-muted">' + collector.description + '</small>';
                    }
                    html += '</label>';
                    html += '</div>';
                });
                $('#collectorsList').html(html);
                
                // 监听采集组件选择变化
                $('.collector-checkbox').change(function() {
                    var checkedCollectors = $('.collector-checkbox:checked').length;
                    $('#submitBatchCollection').prop('disabled', checkedCollectors === 0);
                });
            } else {
                $('#collectorsList').html('<div class="alert alert-warning mb-3">所选服务器没有共同的采集组件，您可以在下方选择采集组件进行批量关联</div>');
                
                // 加载所有采集组件
                $.ajax({
                    url: window.apiCollectorsAllRoute,
                    type: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': window.csrfToken,
                        'Accept': 'application/json'
                    },
                    success: function(response) {
                        if (response.success && response.data.length > 0) {
                            var html = '<div class="form-group">';
                            html += '<div class="custom-control custom-checkbox mb-2">';
                            html += '<input type="checkbox" class="custom-control-input" id="linkCollectors" name="link_collectors" checked>';
                            html += '<label class="custom-control-label" for="linkCollectors">将选择的采集组件关联到未安装该组件的服务器</label>';
                            html += '</div>';
                            html += '</div>';
                            
                            html += '<div class="form-group">';
                            html += '<label>可用采集组件：</label>';
                            response.data.forEach(function(collector) {
                                html += '<div class="form-check">';
                                html += '<input class="form-check-input collector-checkbox" type="checkbox" name="collector_ids[]" value="' + collector.id + '" id="collector_' + collector.id + '">';
                                html += '<label class="form-check-label" for="collector_' + collector.id + '">';
                                html += collector.name + ' (' + collector.code + ')';
                                if (collector.description) {
                                    html += '<br><small class="text-muted">' + collector.description + '</small>';
                                }
                                html += '</label>';
                                html += '</div>';
                            });
                            html += '</div>';
                            
                            $('#collectorsList').append(html);
                            
                            // 监听采集组件选择变化
                            $('.collector-checkbox').change(function() {
                                var checkedCollectors = $('.collector-checkbox:checked').length;
                                $('#submitBatchCollection').prop('disabled', checkedCollectors === 0);
                            });
                        } else {
                            $('#collectorsList').append('<div class="alert alert-danger">没有可用的采集组件</div>');
                        }
                    },
                    error: function(xhr) {
                        $('#collectorsList').append('<div class="alert alert-danger">加载采集组件失败：' + xhr.responseText + '</div>');
                    }
                });
            }
        },
        error: function(xhr) {
            $('#collectorsList').html('<div class="alert alert-danger">加载采集组件失败：' + xhr.responseText + '</div>');
        }
    });
}

// 初始化服务器管理模块
$(document).ready(function() {
    // 全选/取消全选
    $('#selectAll').change(function() {
        $('.server-checkbox').prop('checked', $(this).prop('checked'));
        updateServerButtonStates();
    });
    
    // 单个复选框变化时更新按钮状态
    $('.server-checkbox').change(function() {
        updateServerButtonStates();
    });
    
    // 下载模板
    $('#downloadTemplate').click(function(e) {
        e.preventDefault();
        window.location.href = window.serversDownloadTemplateRoute;
    });
    
    // Excel下载点击事件
    $('#downloadExcel').click(function(e) {
        e.preventDefault();
        downloadServers('xlsx', 'selected');
    });
    
    // CSV下载点击事件
    $('#downloadCsv').click(function(e) {
        e.preventDefault();
        downloadServers('csv', 'selected');
    });
    
    // 已勾选数据下载
    $('#downloadSelected').click(function(e) {
        e.preventDefault();
        showDownloadFormatDialog('selected');
    });
    
    // 当前页数据下载
    $('#downloadCurrentPage').click(function(e) {
        e.preventDefault();
        showDownloadFormatDialog('currentPage');
    });
    
    // 全部查询数据下载
    $('#downloadAllFiltered').click(function(e) {
        e.preventDefault();
        showDownloadFormatDialog('allFiltered');
    });
    
    // 批量采集按钮点击事件
    $('#batchCollectionBtn').click(function() {
        // 获取选中的服务器ID
        var checkedBoxes = $('.server-checkbox:checked');
        if (checkedBoxes.length > 0) {
            var serverIds = [];
            var serverList = '';
            checkedBoxes.each(function() {
                var row = $(this).closest('tr');
                var serverId = $(this).val();
                var serverName = row.find('td:nth-child(3)').text();
                var serverIp = row.find('td:nth-child(5)').text();
                
                serverIds.push(serverId);
                serverList += '<div class="badge badge-info mr-1 mb-1">' + serverName + ' (' + serverIp + ')</div>';
            });
            
            $('#selectedServerCount').text(serverIds.length);
            $('#selectedServerList').html(serverList);
            $('#selected_server_ids').val(serverIds.join(','));
            
            // 加载共同的采集组件
            loadCommonCollectors(serverIds);
            
            // 生成默认任务名称
            var now = new Date();
            var defaultName = '批量采集任务_' + now.getFullYear() + 
                String(now.getMonth() + 1).padStart(2, '0') + 
                String(now.getDate()).padStart(2, '0') + '_' + 
                String(now.getHours()).padStart(2, '0') + 
                String(now.getMinutes()).padStart(2, '0');
            $('#task_name').val(defaultName);
            
            $('#batchCollectionModal').modal('show');
        } else {
            toastr.warning('请先选择要执行采集的服务器');
        }
    });
    
    // 批量修改组件按钮点击事件
    $('#batchModifyComponentsBtn').click(function() {
        var checkedBoxes = $('.server-checkbox:checked');
        if (checkedBoxes.length > 0) {
            var serverIds = [];
            checkedBoxes.each(function() {
                serverIds.push($(this).val());
            });
            
            $('#selectedServerCountModify').text(serverIds.length);
            $('#selected_server_ids_modify').val(serverIds.join(','));
            
            // 加载所有采集组件
            loadAllComponents();
            
            $('#batchModifyComponentsModal').modal('show');
        } else {
            alert('请先选择要修改组件的服务器');
        }
    });
    
    // 批量修改组件表单提交
    $('#submitBatchModifyComponents').click(function() {
        var checkedComponents = $('.component-checkbox:checked').length;
        var operationType = $('input[name="operation_type"]:checked').val();
        
        if (operationType !== 'remove' && checkedComponents === 0) {
            alert('请至少选择一个采集组件');
            return;
        }
        
        if (operationType === 'remove' && checkedComponents === 0) {
            alert('移除操作需要选择要移除的组件');
            return;
        }
        
        var btn = $(this);
        var originalText = btn.html();
        btn.html('<i class="fas fa-spinner fa-spin"></i> 处理中...').prop('disabled', true);
        
        var formData = $('#batchModifyComponentsForm').serialize();
        
        $.ajax({
            url: $('#batchModifyComponentsForm').attr('action'),
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': window.csrfToken,
                'Accept': 'application/json'
            },
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#batchModifyComponentsModal').modal('hide');
                    alert('批量修改组件成功！' + response.message);
                    location.reload();
                } else {
                    alert('修改失败：' + response.message);
                }
                btn.html(originalText).prop('disabled', false);
            },
            error: function(xhr) {
                var errorMessage = '请求失败';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                alert('修改失败：' + errorMessage);
                btn.html(originalText).prop('disabled', false);
            }
        });
    });
    
    // 批量采集表单提交
    $('#batchCollectionForm').submit(function(e) {
        e.preventDefault();
        
        var checkedCollectors = $('.collector-checkbox:checked').length;
        if (checkedCollectors === 0) {
            alert('请至少选择一个采集组件');
            return;
        }
        
        var formData = $(this).serialize();
        var btn = $('#submitBatchCollection');
        var originalText = btn.html();
        var serverIds = $('#selected_server_ids').val().split(',');
        
        btn.html('<i class="fas fa-spinner fa-spin"></i> 创建中...').prop('disabled', true);
        
        // 检查是否需要关联采集组件
        var linkCollectors = $('#linkCollectors').is(':checked');
        
        if (linkCollectors) {
            // 先关联采集组件，再开始采集
            var collectorIds = [];
            $('.collector-checkbox:checked').each(function() {
                collectorIds.push($(this).val());
            });
            
            $.ajax({
                url: window.apiServersBatchAssociateCollectorsRoute,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': window.csrfToken,
                    'Accept': 'application/json'
                },
                data: {
                    server_ids: serverIds,
                    collector_ids: collectorIds
                },
                success: function(response) {
                    if (response.success) {
                        // 关联成功后开始采集
                        startBatchCollection();
                    } else {
                        alert('关联采集组件失败：' + response.message);
                        btn.html(originalText).prop('disabled', false);
                    }
                },
                error: function(xhr) {
                    var errorMsg = '关联采集组件失败';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }
                    alert(errorMsg);
                    btn.html(originalText).prop('disabled', false);
                }
            });
        } else {
            // 直接开始采集
            startBatchCollection();
        }
        
        function startBatchCollection() {
            $.ajax({
                url: $('#batchCollectionForm').attr('action'),
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': window.csrfToken,
                    'Accept': 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                data: formData,
                success: function(response) {
                    if (response.success) {
                        $('#batchCollectionModal').modal('hide');
                        alert('批量采集任务创建成功！正在后台执行...');
                        // 跳转到任务详情页面
                        window.location.href = window.collectionTasksShowRoute.replace(':id', response.data.id);
                    } else {
                        alert('创建失败：' + response.message);
                        btn.html(originalText).prop('disabled', false);
                    }
                },
                error: function(xhr) {
                    var errorMessage = '请求失败';
                    if (xhr.responseJSON) {
                        errorMessage = xhr.responseJSON.message || xhr.responseJSON.error || errorMessage;
                    } else if (xhr.responseText) {
                        errorMessage = xhr.responseText;
                    }
                    alert(errorMessage);
                    btn.html(originalText).prop('disabled', false);
                }
            });
        }
    });
    
    // 初始化按钮状态
    updateServerButtonStates();
});
