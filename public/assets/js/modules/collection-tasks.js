/**
 * 采集任务管理模块
 * 处理采集任务列表页面的所有交互逻辑
 */

// 更新批量删除按钮状态
function updateBatchDeleteTaskButton() {
    var selectedCount = $('.task-checkbox:checked').length;
    $('#batchDeleteBtn').prop('disabled', selectedCount === 0);
}

// 重试任务
window.retryTask = function(taskId) {
    if (confirm("确定要重试失败的任务吗？")) {
        $.ajax({
            url: window.collectionTasksRetryRoute.replace(":id", taskId),
            type: "POST",
            data: {
                _token: window.csrfToken
            },
            success: function(response) {
                if (response.success) {
                    toastr.success("任务重试已启动！");
                    location.reload();
                } else {
                    toastr.error("重试失败：" + response.message);
                }
            },
            error: function(xhr) {
                toastr.error("请求失败：" + (xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : xhr.responseText));
            }
        });
    }
};

// 取消任务
window.cancelTask = function(taskId) {
    if (confirm("确定要取消正在执行的任务吗？")) {
        $.ajax({
            url: window.collectionTasksCancelRoute.replace(":id", taskId),
            type: "POST",
            data: {
                _token: window.csrfToken
            },
            success: function(response) {
                if (response.success) {
                    toastr.success("任务已取消！");
                    location.reload();
                } else {
                    toastr.error("取消失败：" + response.message);
                }
            },
            error: function(xhr) {
                toastr.error("请求失败：" + (xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : xhr.responseText));
            }
        });
    }
};

// 手动触发批量任务
window.triggerBatchTask = function(taskId) {
    if (confirm("确定要手动触发执行此批量任务吗？")) {
        // 禁用相关按钮防止重复点击
        var $button = $('button[onclick*="triggerBatchTask(' + taskId + ')"]');
        $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> 执行中...');
        
        $.ajax({
            url: window.collectionTasksTriggerBatchRoute.replace(":id", taskId),
            type: "POST",
            data: {
                _token: window.csrfToken
            },
            success: function(response) {
                if (response.success) {
                    // 显示成功提示并刷新页面
                    alert("批量任务已开始执行！页面即将刷新...");
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    alert("触发失败：" + response.message);
                    // 恢复按钮状态
                    $button.prop('disabled', false).html('<i class="fas fa-play"></i> 执行');
                }
            },
            error: function(xhr) {
                var errorMsg = "请求失败";
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                } else if (xhr.responseText) {
                    errorMsg = xhr.responseText;
                }
                alert("请求失败：" + errorMsg);
                // 恢复按钮状态
                $button.prop('disabled', false).html('<i class="fas fa-play"></i> 执行');
            }
        });
    }
};

// 初始化采集任务管理模块
$(document).ready(function() {
    // 自动刷新进行中的任务
    setInterval(function() {
        if ($('.badge-warning').length > 0) {
            location.reload();
        }
    }, 5000); // 每5秒刷新一次
    
    // 全选/取消全选
    $('#selectAllTasks').change(function() {
        $('.task-checkbox:not(:disabled)').prop('checked', $(this).prop('checked'));
        updateBatchDeleteTaskButton();
    });
    
    // 单个复选框变化时更新按钮状态
    $('.task-checkbox').change(function() {
        updateBatchDeleteTaskButton();
    });
    
    // 批量删除按钮点击事件
    $('#batchDeleteBtn').click(function() {
        var selectedIds = [];
        $('.task-checkbox:checked').each(function() {
            selectedIds.push($(this).val());
        });
        
        if (selectedIds.length === 0) {
            toastr.warning('请先选择要删除的任务');
            return;
        }
        
        if (confirm('确定要删除选中的 ' + selectedIds.length + ' 个任务吗？此操作不可恢复！')) {
            $.ajax({
                url: window.collectionTasksBatchDestroyRoute,
                type: 'POST',
                data: {
                    _token: window.csrfToken,
                    task_ids: selectedIds
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        // 刷新页面
                        window.location.reload();
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function(xhr) {
                    var errorMsg = '批量删除失败';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }
                    toastr.error(errorMsg);
                }
            });
        }
    });
    
    // 立即执行批量任务按钮点击事件
    $('#batchExecuteBtn').click(function() {
        // 获取所有未执行的批量任务
        var pendingBatchTasks = [];
        $('.task-checkbox').each(function() {
            var taskId = $(this).val();
            var taskType = $(this).closest('tr').find('td:nth-child(4) .badge').text().trim();
            var taskStatus = $(this).closest('tr').find('td:nth-child(5) .badge').text().trim();
            
            if (taskType === '批量服务器' && taskStatus === '未开始') {
                pendingBatchTasks.push({
                    id: taskId,
                    name: $(this).closest('tr').find('td:nth-child(3) a').text().trim()
                });
            }
        });
        
        if (pendingBatchTasks.length === 0) {
            toastr.warning('没有可执行的未开始批量任务');
            return;
        }
        
        // 构建选择列表
        var taskOptions = '';
        pendingBatchTasks.forEach(function(task) {
            taskOptions += '<option value="' + task.id + '">' + task.name + ' (ID: ' + task.id + ')</option>';
        });
        
        // 显示选择对话框
        var selectDialog = $('<div class="modal fade" tabindex="-1" role="dialog">' +
            '<div class="modal-dialog" role="document">' +
            '<div class="modal-content">' +
            '<div class="modal-header">' +
            '<h5 class="modal-title">选择要执行的批量任务</h5>' +
            '<button type="button" class="close" data-dismiss="modal" aria-label="Close">' +
            '<span aria-hidden="true">&times;</span>' +
            '</button>' +
            '</div>' +
            '<div class="modal-body">' +
            '<div class="form-group">' +
            '<label for="taskSelect">选择任务：</label>' +
            '<select class="form-control" id="taskSelect">' +
            taskOptions +
            '</select>' +
            '</div>' +
            '</div>' +
            '<div class="modal-footer">' +
            '<button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button>' +
            '<button type="button" class="btn btn-primary" id="confirmExecuteBtn">确认执行</button>' +
            '</div>' +
            '</div>' +
            '</div>' +
            '</div>');
        
        $('body').append(selectDialog);
        selectDialog.modal('show');
        
        // 确认执行按钮点击事件
        $('#confirmExecuteBtn').click(function() {
            var taskId = $('#taskSelect').val();
            
            $.ajax({
                url: window.collectionTasksTriggerRoute.replace(':id', taskId),
                type: 'POST',
                data: {
                    _token: window.csrfToken
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        selectDialog.modal('hide');
                        // 刷新页面
                        window.location.reload();
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function(xhr) {
                    var errorMsg = '触发任务失败';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }
                    toastr.error(errorMsg);
                }
            });
        });
        
        // 模态框关闭时移除
        selectDialog.on('hidden.bs.modal', function() {
            $(this).remove();
        });
    });
});
