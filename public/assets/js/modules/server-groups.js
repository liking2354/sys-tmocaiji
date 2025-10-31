/**
 * 服务器分组管理模块
 * 处理服务器分组列表页面的所有交互逻辑
 * 
 * 设计原则：
 * 1. 不使用 $(document).on() 事件委托，避免事件冒泡到 document 级别
 * 2. 直接在元素上绑定事件，保证事件隔离
 * 3. 防止与导航栏事件处理器冲突
 */

// 更新批量删除按钮状态
function updateBatchDeleteButton() {
    var checkedCount = $('.group-checkbox:checked').length;
    console.log('选中的复选框数量:', checkedCount);
    
    if (checkedCount > 0) {
        $('#batch-delete-btn').prop('disabled', false);
    } else {
        $('#batch-delete-btn').prop('disabled', true);
    }
}

// 创建变更任务函数
function createChangeTask(groupId, groupName) {
    // 跳转到创建变更任务页面，并预选该服务器分组
    const url = new URL(window.systemChangeTasksCreateRoute, window.location.origin);
    url.searchParams.set('server_group_id', groupId);
    url.searchParams.set('server_group_name', groupName);
    window.location.href = url.toString();
}

// 初始化服务器分组管理模块
$(document).ready(function() {
    // 初始化时检查按钮状态
    updateBatchDeleteButton();
    
    // 全选/取消全选 - 直接在元素上绑定，不使用 $(document).on()
    var selectAllCheckbox = document.getElementById('select-all');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            var isChecked = this.checked;
            var checkboxes = document.querySelectorAll('.group-checkbox');
            checkboxes.forEach(function(checkbox) {
                checkbox.checked = isChecked;
            });
            updateBatchDeleteButton();
        });
    }
    
    // 单个复选框变化时更新全选框状态 - 直接在元素上绑定
    var groupCheckboxes = document.querySelectorAll('.group-checkbox');
    groupCheckboxes.forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            updateBatchDeleteButton();
            
            // 如果所有复选框都选中，则全选框也选中
            var totalCheckboxes = document.querySelectorAll('.group-checkbox').length;
            var checkedCheckboxes = document.querySelectorAll('.group-checkbox:checked').length;
            
            var selectAllCheckbox = document.getElementById('select-all');
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = totalCheckboxes > 0 && checkedCheckboxes === totalCheckboxes;
            }
        });
    });
    
    // 批量删除按钮点击事件 - 直接在元素上绑定
    var batchDeleteBtn = document.getElementById('batch-delete-btn');
    if (batchDeleteBtn) {
        batchDeleteBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var checkedCount = document.querySelectorAll('.group-checkbox:checked').length;
            if (checkedCount === 0) {
                alert('请至少选择一个分组');
                return false;
            }
            
            // 显示确认对话框
            if (confirm('确定要删除选中的分组吗？此操作不可恢复！')) {
                // 直接提交表单
                var batchForm = document.getElementById('batch-form');
                if (batchForm) {
                    batchForm.submit();
                }
            }
        });
    }
});
