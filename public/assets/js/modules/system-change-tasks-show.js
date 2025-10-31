/**
 * 系统变更任务详情模块
 * 功能：任务执行、进度管理、还原操作
 */

// 全局变量
let taskId = window.taskId || 0;

/**
 * 执行任务并显示进度
 */
function executeTaskWithProgress(taskId) {
    if (window.executionProgressManager) {
        // 使用进度管理器执行任务
        executeTaskWithProgressManager(taskId);
    } else {
        // 降级处理：直接执行任务
        executeTask(taskId);
    }
}

/**
 * 使用进度管理器执行任务
 */
function executeTaskWithProgressManager(taskId) {
    const steps = [
        '准备执行环境',
        '连接目标服务器',
        '备份原始文件',
        '执行配置变更',
        '验证变更结果',
        '完成任务执行'
    ];
    
    // 初始化进度管理器
    window.executionProgressManager.init('执行系统变更任务', steps, () => executeTaskWithProgressManager(taskId));
    
    // 开始执行任务
    executeSystemChangeTask(taskId);
}

/**
 * 执行系统变更任务的具体逻辑
 */
function executeSystemChangeTask(taskId) {
    const progressManager = window.executionProgressManager;
    if (!progressManager) {
        executeTask(taskId);
        return;
    }
    
    // 步骤1: 准备执行环境
    progressManager.startStep(0, '正在准备执行环境...');
    
    setTimeout(() => {
        progressManager.completeStep(0, true, '执行环境准备完成');
        
        // 步骤2: 开始执行任务
        progressManager.startStep(1, '正在执行任务...');
        
        $.ajax({
            url: '/system-change/tasks/' + taskId + '/execute',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    // 模拟完成所有步骤
                    for (let i = 1; i < 6; i++) {
                        progressManager.completeStep(i, true);
                    }
                    
                    progressManager.showResult(true, '任务执行成功', '系统变更任务已成功执行完成！');
                    
                    // 3秒后自动刷新页面
                    setTimeout(() => {
                        location.reload();
                    }, 3000);
                } else {
                    progressManager.completeStep(1, false, response.message || '任务执行失败');
                    progressManager.showResult(false, '任务执行失败', response.message || '未知错误');
                }
            },
            error: function(xhr) {
                let message = '任务执行失败';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                
                progressManager.completeStep(1, false, message);
                progressManager.showResult(false, '任务执行失败', message);
            }
        });
    }, 1000);
}

/**
 * 降级执行函数
 */
function executeTask(taskId) {
    if (confirm('确定要执行此任务吗？')) {
        $('button[onclick="executeTask(' + taskId + ')"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> 执行中...');
        
        $.ajax({
            url: '/system-change/tasks/' + taskId + '/execute',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    alert('任务执行成功！');
                    location.reload();
                } else {
                    alert('任务执行失败：' + (response.message || '未知错误'));
                    $('button[onclick="executeTask(' + taskId + ')"]').prop('disabled', false).html('<i class="fas fa-play"></i> 执行任务');
                }
            },
            error: function(xhr) {
                var message = '任务执行失败';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message += '：' + xhr.responseJSON.message;
                }
                alert(message);
                $('button[onclick="executeTask(' + taskId + ')"]').prop('disabled', false).html('<i class="fas fa-play"></i> 执行任务');
            }
        });
    }
}

/**
 * 还原单个任务详情
 */
function revertTaskDetail(detailId) {
    if (confirm('确定要还原此变更吗？还原后将恢复到执行前的状态。')) {
        var $btn = $('button[onclick="revertTaskDetail(' + detailId + ')"]');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> 还原中...');
        
        $.ajax({
            url: '/system-change/task-details/' + detailId + '/revert',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    alert('还原成功！');
                    location.reload();
                } else {
                    alert('还原失败：' + (response.message || '未知错误'));
                    $btn.prop('disabled', false).html('<i class="fas fa-undo"></i> 还原');
                }
            },
            error: function(xhr) {
                var message = '还原失败';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message += '：' + xhr.responseJSON.message;
                }
                alert(message);
                $btn.prop('disabled', false).html('<i class="fas fa-undo"></i> 还原');
            }
        });
    }
}

/**
 * 还原整个任务
 */
function revertAllTask(taskId) {
    if (confirm('确定要还原整个任务的所有变更吗？这将恢复所有已执行的配置到执行前的状态。')) {
        var $btn = $('button[onclick="revertAllTask(' + taskId + ')"]');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> 还原中...');
        
        $.ajax({
            url: '/system-change/tasks/' + taskId + '/revert',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    alert('任务还原成功！');
                    location.reload();
                } else {
                    alert('任务还原失败：' + (response.message || '未知错误'));
                    $btn.prop('disabled', false).html('<i class="fas fa-undo"></i> 还原整个任务');
                }
            },
            error: function(xhr) {
                var message = '任务还原失败';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message += '：' + xhr.responseJSON.message;
                }
                alert(message);
                $btn.prop('disabled', false).html('<i class="fas fa-undo"></i> 还原整个任务');
            }
        });
    }
}

/**
 * 批量还原选中的任务详情
 */
function batchRevertSelected() {
    var selectedIds = [];
    $('.detail-checkbox:checked').each(function() {
        selectedIds.push($(this).val());
    });
    
    if (selectedIds.length === 0) {
        alert('请先选择要还原的项目');
        return;
    }
    
    if (confirm('确定要还原选中的 ' + selectedIds.length + ' 个变更吗？')) {
        var $btn = $('#batchRevertBtn');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> 批量还原中...');
        
        $.ajax({
            url: '/system-change/task-details/batch-revert',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: {
                detail_ids: selectedIds
            },
            success: function(response) {
                alert(response.message || '批量还原完成');
                location.reload();
            },
            error: function(xhr) {
                var message = '批量还原失败';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message += '：' + xhr.responseJSON.message;
                }
                alert(message);
                $btn.prop('disabled', false).html('<i class="fas fa-undo"></i> 批量还原选中');
            }
        });
    }
}

/**
 * 初始化事件处理
 */
$(document).ready(function() {
    // 确保进度管理器正确初始化
    setTimeout(function() {
        if (typeof initExecutionProgressManager === 'function') {
            initExecutionProgressManager();
        }
    }, 100);
    
    // 全选/取消全选
    $('#selectAll').change(function() {
        $('.detail-checkbox').prop('checked', $(this).prop('checked'));
        toggleBatchRevertButton();
    });
    
    // 单个复选框变化
    $('.detail-checkbox').change(function() {
        var totalCheckboxes = $('.detail-checkbox').length;
        var checkedCheckboxes = $('.detail-checkbox:checked').length;
        
        $('#selectAll').prop('checked', totalCheckboxes === checkedCheckboxes);
        toggleBatchRevertButton();
    });
});

/**
 * 切换批量还原按钮显示
 */
function toggleBatchRevertButton() {
    var checkedCount = $('.detail-checkbox:checked').length;
    if (checkedCount > 0) {
        $('#batchRevertBtn').show();
    } else {
        $('#batchRevertBtn').hide();
    }
}
