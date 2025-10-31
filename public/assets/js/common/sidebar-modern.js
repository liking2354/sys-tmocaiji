/* ============================================
   现代化侧边栏脚本
   ============================================ */

const SidebarModern = (function() {
    'use strict';
    
    let initialized = false;
    const STORAGE_KEY = 'sidebar-collapsed';
    
    /**
     * 初始化侧边栏
     */
    function init() {
        if (initialized) {
            return;
        }
        initialized = true;
        
        initToggle();
        initSubmenu();
        restoreState();
    }
    
    /**
     * 初始化侧边栏切换按钮
     */
    function initToggle() {
        const toggleBtn = document.getElementById('sidebar-toggle');
        if (!toggleBtn) return;
        
        toggleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            toggleSidebar();
        });
    }
    
    /**
     * 切换侧边栏
     */
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.querySelector('.main-content');
        const toggleIcon = document.getElementById('toggle-icon');
        
        if (!sidebar) return;
        
        const isCollapsed = sidebar.classList.contains('sidebar-collapsed');
        
        if (isCollapsed) {
            // 展开
            sidebar.classList.remove('sidebar-collapsed');
            if (mainContent) {
                mainContent.classList.remove('main-content-expanded');
            }
            if (toggleIcon) {
                toggleIcon.classList.remove('fa-chevron-right');
                toggleIcon.classList.add('fa-chevron-left');
            }
            localStorage.setItem(STORAGE_KEY, 'false');
        } else {
            // 收起
            sidebar.classList.add('sidebar-collapsed');
            if (mainContent) {
                mainContent.classList.add('main-content-expanded');
            }
            if (toggleIcon) {
                toggleIcon.classList.remove('fa-chevron-left');
                toggleIcon.classList.add('fa-chevron-right');
            }
            localStorage.setItem(STORAGE_KEY, 'true');
        }
    }
    
    /**
     * 初始化子菜单
     */
    function initSubmenu() {
        const submenuToggles = document.querySelectorAll('.sidebar-submenu-toggle');
        
        submenuToggles.forEach(toggle => {
            // 初始化子菜单状态
            const submenu = toggle.nextElementSibling;
            if (!submenu || !submenu.classList.contains('sidebar-submenu')) {
                return;
            }
            
            // 检查是否有 active 子菜单项
            const hasActiveChild = submenu.querySelector('.nav-link.active');
            
            if (hasActiveChild) {
                // 展开菜单
                submenu.classList.remove('collapsed');
                updateToggleIcon(toggle, true);
            } else {
                // 收起菜单
                submenu.classList.add('collapsed');
                updateToggleIcon(toggle, false);
            }
            
            // 添加点击事件
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                toggleSubmenu(this);
            });
        });
    }
    
    /**
     * 切换子菜单
     */
    function toggleSubmenu(toggle) {
        const submenu = toggle.nextElementSibling;
        if (!submenu || !submenu.classList.contains('sidebar-submenu')) {
            return;
        }
        
        const isCollapsed = submenu.classList.contains('collapsed');
        
        if (isCollapsed) {
            // 展开
            submenu.classList.remove('collapsed');
            updateToggleIcon(toggle, true);
        } else {
            // 收起
            submenu.classList.add('collapsed');
            updateToggleIcon(toggle, false);
        }
    }
    
    /**
     * 更新切换图标
     */
    function updateToggleIcon(toggle, isOpen) {
        const icon = toggle.querySelector('.submenu-icon');
        if (!icon) return;
        
        if (isOpen) {
            icon.classList.remove('fa-chevron-down');
            icon.classList.add('fa-chevron-up');
        } else {
            icon.classList.remove('fa-chevron-up');
            icon.classList.add('fa-chevron-down');
        }
    }
    
    /**
     * 恢复侧边栏状态
     */
    function restoreState() {
        const isCollapsed = localStorage.getItem(STORAGE_KEY) === 'true';
        
        if (isCollapsed) {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.querySelector('.main-content');
            const toggleIcon = document.getElementById('toggle-icon');
            
            if (sidebar) {
                sidebar.classList.add('sidebar-collapsed');
            }
            if (mainContent) {
                mainContent.classList.add('main-content-expanded');
            }
            if (toggleIcon) {
                toggleIcon.classList.remove('fa-chevron-left');
                toggleIcon.classList.add('fa-chevron-right');
            }
        }
    }
    
    /**
     * 公开 API
     */
    return {
        init: init,
        toggle: toggleSidebar
    };
})();

// 页面加载时初始化
document.addEventListener('DOMContentLoaded', function() {
    SidebarModern.init();
});
