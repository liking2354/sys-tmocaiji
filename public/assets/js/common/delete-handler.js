/**
 * 通用删除处理函数
 * 用于统一处理所有页面的删除操作
 */

/**
 * 删除单个项目
 * @param {string} routeUrl - 删除路由的完整URL
 * @param {string} confirmMessage - 确认提示信息
 */
function deleteItem(routeUrl, confirmMessage = '确定要删除吗？') {
    if (confirm(confirmMessage)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = routeUrl;
        form.innerHTML = `<input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]').getAttribute('content')}"><input type="hidden" name="_method" value="DELETE">`;
        document.body.appendChild(form);
        form.submit();
    }
}

/**
 * 删除服务器
 * @param {number} serverId - 服务器ID
 */
function deleteServer(serverId) {
    deleteItem(
        `${window.location.origin}/servers/${serverId}`,
        '确定要删除该服务器吗？'
    );
}

/**
 * 删除采集组件
 * @param {number} collectorId - 采集组件ID
 */
function deleteCollector(collectorId) {
    deleteItem(
        `${window.location.origin}/collectors/${collectorId}`,
        '确定要删除该采集组件吗？'
    );
}

/**
 * 删除采集任务
 * @param {number} taskId - 采集任务ID
 */
function deleteCollectionTask(taskId) {
    deleteItem(
        `${window.location.origin}/collection-tasks/${taskId}`,
        '确定要删除该任务吗？'
    );
}

/**
 * 删除系统变更模板
 * @param {number} templateId - 模板ID
 */
function deleteTemplate(templateId) {
    deleteItem(
        `${window.location.origin}/system-change/templates/${templateId}`,
        '确定要删除这个模板吗？'
    );
}

/**
 * 删除系统变更任务
 * @param {number} taskId - 任务ID
 */
function deleteChangeTask(taskId) {
    deleteItem(
        `${window.location.origin}/system-change/tasks/${taskId}`,
        '确定要删除这个任务吗？'
    );
}

/**
 * 删除用户
 * @param {number} userId - 用户ID
 */
function deleteUser(userId) {
    deleteItem(
        `${window.location.origin}/admin/users/${userId}`,
        '确定要删除该用户吗？'
    );
}

/**
 * 删除角色
 * @param {number} roleId - 角色ID
 */
function deleteRole(roleId) {
    deleteItem(
        `${window.location.origin}/admin/roles/${roleId}`,
        '确定要删除该角色吗？'
    );
}

/**
 * 删除权限
 * @param {number} permissionId - 权限ID
 */
function deletePermission(permissionId) {
    deleteItem(
        `${window.location.origin}/admin/permissions/${permissionId}`,
        '确定要删除该权限吗？'
    );
}

/**
 * 提交表单（用于复制、切换状态等操作）
 * @param {HTMLFormElement} form - 要提交的表单元素
 */
function submitForm(form) {
    form.submit();
}
