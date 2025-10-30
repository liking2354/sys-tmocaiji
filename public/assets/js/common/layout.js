/* ============================================
   布局脚本 - 导航栏、侧边栏交互
   ============================================ */

const Layout = (function() {
    'use strict';
    
    /**
     * 初始化侧边栏
     */
    function initSidebar() {
        // 侧边栏展开/收起功能
        $('#sidebar-toggle').click(function() {
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
        
        // 菜单初始化逻辑
        $('.sidebar-submenu-toggle').each(function() {
            const $this = $(this);
            const $submenu = $this.next('.sidebar-submenu');
            const $icon = $this.find('.fas.float-right');
            
            // 基础设施菜单保持当前状态
            if ($this.text().trim().includes('基础设施')) {
                // 不做任何自动操作，保持HTML中设置的状态
            }
            // 系统管理菜单根据当前路由决定是否展开
            else if ($this.text().trim().includes('系统管理')) {
                if (window.location.pathname.includes('/admin/')) {
                    $this.removeClass('collapsed');
                    $submenu.show();
                    $icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
                } else {
                    $this.addClass('collapsed');
                    $submenu.hide();
                    $icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
                }
            }
        });
        
        // 子菜单展开/收起功能
        $('.sidebar-submenu-toggle').click(function(e) {
            e.preventDefault();
            const $this = $(this);
            const $submenu = $this.next('.sidebar-submenu');
            const $icon = $this.find('.fas.float-right');
            
            $this.toggleClass('collapsed');
            $submenu.slideToggle(300);
            
            if ($this.hasClass('collapsed')) {
                $icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
            } else {
                $icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
            }
        });
        
        // 阻止子菜单项点击时触发父菜单收起
        $('.sidebar-submenu .nav-link').click(function(e) {
            e.stopPropagation();
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
