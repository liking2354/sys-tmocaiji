/* ============================================
   布局脚本 - 导航栏、侧边栏交互（调试版本）
   ============================================ */

const Layout = (function() {
    'use strict';
    
    // 防止重复初始化
    let initialized = false;
    
    /**
     * 初始化侧边栏
     */
    function initSidebar() {
        // 防止重复初始化
        if (initialized) {
            console.log('[Layout] 已初始化，跳过重复初始化');
            return;
        }
        initialized = true;
        console.log('[Layout] 开始初始化侧边栏');
        
        // 侧边栏展开/收起功能
        $(document).off('click', '#sidebar-toggle').on('click', '#sidebar-toggle', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('[Layout] 侧边栏切换按钮被点击');
            
            $('#sidebar').toggleClass('sidebar-collapsed');
            $('#main-content').toggleClass('main-content-expanded');
            
            // 切换图标
            if ($('#sidebar').hasClass('sidebar-collapsed')) {
                $('#toggle-icon').removeClass('fa-chevron-left').addClass('fa-chevron-right');
            } else {
                $('#toggle-icon').removeClass('fa-chevron-right').addClass('fa-chevron-left');
            }
            
            // 保存状态到本地存储
            localStorage.setItem('sidebar-collapsed', $('#sidebar').hasClass('sidebar-collapsed'));
        });
        
        // 初始化所有子菜单的展开/收起状态
        console.log('[Layout] 初始化子菜单状态');
        $('.sidebar-submenu-toggle').each(function(index) {
            const $this = $(this);
            const $submenu = $this.next('.sidebar-submenu');
            const $icon = $this.find('.fas.float-right');
            const menuName = $this.text().trim();
            
            // 检查是否有 active 子菜单项
            const hasActiveChild = $submenu.find('.nav-link.active').length > 0;
            
            console.log(`[Layout] 菜单 ${index}: "${menuName}" - 有 active 子菜单: ${hasActiveChild}`);
            
            if (hasActiveChild) {
                // 如果有 active 子菜单项，展开菜单
                $submenu.css('display', 'block');
                // 更新图标
                $icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
                console.log(`[Layout] 菜单 "${menuName}" 已展开`);
            } else {
                // 否则收起菜单
                $submenu.css('display', 'none');
                // 更新图标
                $icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
                console.log(`[Layout] 菜单 "${menuName}" 已收起`);
            }
        });
        
        // 子菜单展开/收起功能 - 使用事件委托
        $(document).off('click', '.sidebar-submenu-toggle').on('click', '.sidebar-submenu-toggle', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const $this = $(this);
            const $submenu = $this.next('.sidebar-submenu');
            const $icon = $this.find('.fas.float-right');
            const menuName = $this.text().trim();
            
            // 检查当前菜单是否显示
            const isVisible = $submenu.is(':visible');
            
            console.log(`[Layout] 菜单 "${menuName}" 被点击 - 当前状态: ${isVisible ? '显示' : '隐藏'}`);
            
            if (isVisible) {
                // 如果显示，则隐藏
                console.log(`[Layout] 菜单 "${menuName}" 开始隐藏...`);
                $submenu.slideUp(300, function() {
                    console.log(`[Layout] 菜单 "${menuName}" 隐藏完成`);
                });
                $icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
            } else {
                // 如果隐藏，则显示
                console.log(`[Layout] 菜单 "${menuName}" 开始显示...`);
                $submenu.slideDown(300, function() {
                    console.log(`[Layout] 菜单 "${menuName}" 显示完成`);
                });
                $icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
            }
        });
        
        // 阻止子菜单项点击时触发父菜单收起
        $(document).off('click', '.sidebar-submenu .nav-link').on('click', '.sidebar-submenu .nav-link', function(e) {
            e.stopPropagation();
            const linkText = $(this).text().trim();
            console.log(`[Layout] 子菜单项 "${linkText}" 被点击`);
        });
        
        // 页面加载时恢复侧边栏状态
        if (localStorage.getItem('sidebar-collapsed') === 'true') {
            $('#sidebar').addClass('sidebar-collapsed');
            $('#main-content').addClass('main-content-expanded');
            $('#toggle-icon').removeClass('fa-chevron-left').addClass('fa-chevron-right');
            console.log('[Layout] 侧边栏已恢复为收起状态');
        }
        
        console.log('[Layout] 侧边栏初始化完成');
    }
    
    /**
     * 初始化导航栏
     */
    function initNavbar() {
        console.log('[Layout] 初始化导航栏');
        // 导航栏下拉菜单
        $('.navbar-nav .dropdown-toggle').click(function(e) {
            e.preventDefault();
            $(this).next('.dropdown-menu').toggle();
        });
        
        // 点击其他地方关闭下拉菜单
        $(document).click(function(e) {
            if (!$(e.target).closest('.navbar-nav').length) {
                $('.dropdown-menu').hide();
            }
        });
    }
    
    /**
     * 初始化提示信息
     */
    function initAlerts() {
        console.log('[Layout] 初始化提示信息');
        // 关闭提示信息
        $('.alert .close').click(function() {
            $(this).closest('.alert').fadeOut(300, function() {
                $(this).remove();
            });
        });
        
        // 自动关闭成功提示
        $('.alert-success').each(function() {
            const $alert = $(this);
            setTimeout(function() {
                $alert.fadeOut(300, function() {
                    $alert.remove();
                });
            }, 5000);
        });
    }
    
    /**
     * 初始化所有功能
     */
    function init() {
        console.log('[Layout] 开始初始化所有功能');
        initSidebar();
        initNavbar();
        initAlerts();
        console.log('[Layout] 所有功能初始化完成');
    }
    
    // 公开接口
    return {
        init,
        initSidebar,
        initNavbar,
        initAlerts
    };
})();

// 页面加载完成后初始化
$(document).ready(function() {
    console.log('[Layout] 文档已加载，开始初始化');
    Layout.init();
});

// 监听页面可见性变化
document.addEventListener('visibilitychange', function() {
    if (!document.hidden) {
        console.log('[Layout] 页面重新获得焦点');
    }
});
