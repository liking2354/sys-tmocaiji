/**
 * 服务器编辑模块
 * 功能：服务器编辑表单处理、连接验证
 */

$(document).ready(function() {
    // 测试连接按钮点击事件
    $('#testConnectionBtn').click(function() {
        var ip = $('#ip').val();
        var port = $('#port').val();
        var username = $('#username').val();
        var password = $('#password').val();
        
        if (!ip || !port || !username) {
            alert('请填写IP、端口和用户名');
            return;
        }
        
        // 显示加载状态
        var btn = $(this);
        var originalText = btn.html();
        btn.html('<i class="fas fa-spinner fa-spin"></i> 测试中...');
        btn.prop('disabled', true);
        
        // 发送AJAX请求验证连接
        $.ajax({
            url: window.serverVerifyUrl,
            type: 'POST',
            data: {
                _token: window.csrfToken,
                ip: ip,
                port: port,
                username: username,
                password: password || window.originalPassword
            },
            success: function(response) {
                if (response.success) {
                    alert('连接成功！');
                } else {
                    alert('连接失败：' + response.message);
                }
            },
            error: function(xhr) {
                alert('请求失败：' + xhr.responseText);
            },
            complete: function() {
                // 恢复按钮状态
                btn.html(originalText);
                btn.prop('disabled', false);
            }
        });
    });
});
