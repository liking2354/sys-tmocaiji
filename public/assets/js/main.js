/* ============================================
   主脚本文件 - 加载所有模块
   ============================================ */

// 页面加载完成后执行
$(document).ready(function() {
    // 初始化布局
    if (typeof Layout !== 'undefined') {
        Layout.init();
    }
    
    // 初始化通知系统
    if (typeof Notifications !== 'undefined') {
        // 通知系统已初始化
    }
    
    // 初始化 API 系统
    if (typeof API !== 'undefined') {
        // API 系统已初始化
    }
    
    // 初始化工具函数
    if (typeof Utils !== 'undefined') {
        // 工具函数已初始化
    }
    
    // 全局错误处理
    if (typeof API !== 'undefined') {
        API.onError(function(jqxhr, settings, thrownError) {
            console.error('AJAX 错误:', thrownError);
            
            // 处理特定的错误状态码
            if (jqxhr.status === 401) {
                Notifications.error('您的登录已过期，请重新登录');
                window.location.href = '/login';
            } else if (jqxhr.status === 403) {
                Notifications.error('您没有权限执行此操作');
            } else if (jqxhr.status === 404) {
                Notifications.error('请求的资源不存在');
            } else if (jqxhr.status === 500) {
                Notifications.error('服务器错误，请稍后重试');
            } else if (jqxhr.status === 0) {
                Notifications.error('网络连接失败');
            }
        });
    }
});

// 页面卸载前的处理
$(window).on('beforeunload', function() {
    // 可以在这里添加页面卸载前的处理逻辑
});

// 处理页面可见性变化
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        // 页面隐藏
        console.log('页面已隐藏');
    } else {
        // 页面显示
        console.log('页面已显示');
    }
});
