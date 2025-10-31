/**
 * 系统变更模板详情模块
 * 功能：模板详情展示、复制、状态切换
 */

/**
 * 复制模板
 */
function duplicateTemplate() {
    if (confirm('确定要复制这个模板吗？')) {
        $.post(window.duplicateTemplateUrl, {
            _token: window.csrfToken
        }).done(function(response) {
            if (response.success) {
                alert('模板复制成功！');
                window.location.href = response.redirect_url;
            } else {
                alert('复制失败：' + response.message);
            }
        }).fail(function() {
            alert('复制失败，请稍后重试');
        });
    }
}

/**
 * 切换模板状态
 */
function toggleStatus() {
    const currentStatus = window.templateIsActive;
    const action = currentStatus ? '禁用' : '启用';
    
    if (confirm('确定要' + action + '这个模板吗？')) {
        $.post(window.toggleStatusUrl, {
            _token: window.csrfToken
        }).done(function(response) {
            if (response.success) {
                alert(action + '成功！');
                location.reload();
            } else {
                alert(action + '失败：' + response.message);
            }
        }).fail(function() {
            alert(action + '失败，请稍后重试');
        });
    }
}
