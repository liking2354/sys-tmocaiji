/**
 * 管理后台 - 操作日志模块
 * 功能：日志搜索、导出、清理、批量删除
 */

/**
 * 初始化事件处理
 */
$(document).ready(function() {
    // 搜索表单折叠控制
    $('#searchToggleBtn').on('click', function() {
        var icon = $('#searchToggleIcon');
        var isExpanded = $('#searchForm').hasClass('show');
        
        if (isExpanded) {
            icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
        } else {
            icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
        }
    });

    // 全选/取消全选
    $('#selectAll').on('change', function() {
        var isChecked = $(this).is(':checked');
        $('.log-checkbox').prop('checked', isChecked);
        toggleBatchDeleteBtn();
    });

    // 单个复选框变化
    $('.log-checkbox').on('change', function() {
        var totalCheckboxes = $('.log-checkbox').length;
        var checkedCheckboxes = $('.log-checkbox:checked').length;
        
        $('#selectAll').prop('checked', totalCheckboxes === checkedCheckboxes);
        toggleBatchDeleteBtn();
    });

    // 模态框焦点管理
    $('.modal').on('hide.bs.modal', function () {
        $(this).find(':focus').blur();
    });

    $('.modal').on('shown.bs.modal', function () {
        $(this).find('input, select, textarea, button').filter(':visible').first().focus();
    });
});

/**
 * 切换批量删除按钮显示
 */
function toggleBatchDeleteBtn() {
    var checkedCount = $('.log-checkbox:checked').length;
    if (checkedCount > 0) {
        $('#batchDeleteBtn').show();
    } else {
        $('#batchDeleteBtn').hide();
    }
}

/**
 * 显示导出模态框
 */
function showExportModal() {
    $('#exportModal').modal('show');
}

/**
 * 确认导出
 */
function confirmExport() {
    var formData = $('#exportForm').serialize();
    $('#exportModal').find(':focus').blur();
    $('#exportModal').modal('hide');
    
    // 构建导出URL
    var exportUrl = window.exportRoute + '?' + formData;
    window.location.href = exportUrl;
}

/**
 * 显示清理模态框
 */
function showCleanupModal() {
    $('#cleanupModal').modal('show');
}

/**
 * 确认清理
 */
function confirmCleanup() {
    var days = $('#cleanup_days').val();
    
    $.ajax({
        url: window.cleanupRoute,
        method: 'POST',
        data: {
            days: days,
            _token: window.csrfToken
        },
        success: function(response) {
            $('#cleanupModal').find(':focus').blur();
            $('#cleanupModal').modal('hide');
            
            if (response.success) {
                alert('清理成功：' + response.message);
                location.reload();
            } else {
                alert('清理失败：' + response.message);
            }
        },
        error: function(xhr) {
            $('#cleanupModal').find(':focus').blur();
            $('#cleanupModal').modal('hide');
            alert('清理失败，请稍后重试');
        }
    });
}

/**
 * 批量删除
 */
function batchDelete() {
    var selectedIds = $('.log-checkbox:checked').map(function() {
        return $(this).val();
    }).get();
    
    if (selectedIds.length === 0) {
        alert('请选择要删除的日志记录');
        return;
    }
    
    if (!confirm('确定要删除选中的 ' + selectedIds.length + ' 条日志记录吗？此操作无法撤销！')) {
        return;
    }
    
    $.ajax({
        url: window.batchDeleteRoute,
        method: 'POST',
        data: {
            ids: selectedIds,
            _token: window.csrfToken
        },
        success: function(response) {
            if (response.success) {
                alert('删除成功：' + response.message);
                location.reload();
            } else {
                alert('删除失败：' + response.message);
            }
        },
        error: function(xhr) {
            alert('删除失败，请稍后重试');
        }
    });
}
