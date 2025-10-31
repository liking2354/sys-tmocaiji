/* ============================================
   布局脚本 - 导航栏、侧边栏交互
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
            return;
        }
        initialized = true;
        
        // 侧边栏展开/收起功能
        $('#sidebar').off('click', '#sidebar-toggle').on('click', '#sidebar-toggle', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
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
        $('.sidebar-submenu-toggle').each(function() {
            const $this = $(this);
            const $submenu = $this.next('.sidebar-submenu');
            const $icon = $this.find('.submenu-icon');
            
            // 检查是否有 active 子菜单项
            const hasActiveChild = $submenu.find('.nav-link.active').length > 0;
            
            // 移除内联样式，使用 collapsed 类控制
            $submenu.removeAttr('style');
            
            if (hasActiveChild) {
                // 如果有 active 子菜单项，展开菜单
                $submenu.removeClass('collapsed');
                $icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
            } else {
                // 否则收起菜单
                $submenu.addClass('collapsed');
                $icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
            }
        });
        
        // 子菜单展开/收起功能 - 使用 event.target 精确判断
        $(document).off('click.sidebar-toggle').on('click.sidebar-toggle', '.sidebar-submenu-toggle', function(e) {
            // 只处理点击在菜单项本身或其直接子元素（图标、文字）上的情况
            // 不处理点击在子菜单内的情况
            const $target = $(e.target);
            
            // 如果点击的是子菜单内的链接，则不处理
            if ($target.closest('.sidebar-submenu').length > 0) {
                return;
            }
            
            e.preventDefault();
            e.stopPropagation();
            
            const $menuItem = $(this);
            const $submenu = $menuItem.next('.sidebar-submenu');
            const $icon = $menuItem.find('.submenu-icon');
            
            // 切换 collapsed 类
            $submenu.toggleClass('collapsed');
            
            // 切换图标
            if ($submenu.hasClass('collapsed')) {
                $icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
            } else {
                $icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
            }
        });
        
        // 页面加载时恢复侧边栏状态
        if (localStorage.getItem('sidebar-collapsed') === 'true') {
            $('#sidebar').addClass('sidebar-collapsed');
            $('#main-content').addClass('main-content-expanded');
            $('#toggle-icon').removeClass('fa-chevron-left').addClass('fa-chevron-right');
        }
    }
    
    /**
     * 初始化导航栏
     */
    function initNavbar() {
        // 导航栏下拉菜单
        $(document).off('click.navbar-dropdown').on('click.navbar-dropdown', '.navbar-nav .dropdown-toggle', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).next('.dropdown-menu').toggle();
        });
        
        // 点击其他地方关闭下拉菜单
        $(document).off('click.navbar-close').on('click.navbar-close', function(e) {
            if (!$(e.target).closest('.navbar-nav').length) {
                $('.dropdown-menu').hide();
            }
        });
    }
    
    /**
     * 初始化提示信息
     */
    function initAlerts() {
        // 关闭提示信息
        $(document).off('click.alert-close').on('click.alert-close', '.alert .close', function() {
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
        initSidebar();
        initNavbar();
        initAlerts();
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
    Layout.init();
});
