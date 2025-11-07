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
        initCollapsedHover();
        restoreState();
    }
    
    /**
     * 初始化侧边栏切换按钮
     */
    function initToggle() {
        const toggleBtn = document.getElementById('sidebar-toggle-navbar');
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
        const toggleIcon = document.getElementById('toggle-icon-navbar');
        
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
                toggleIcon.classList.add('fa-bars');
                toggleIcon.style.transform = 'rotate(0deg)';
            }
            localStorage.setItem(STORAGE_KEY, 'false');
        } else {
            // 收起
            sidebar.classList.add('sidebar-collapsed');
            if (mainContent) {
                mainContent.classList.add('main-content-expanded');
            }
            if (toggleIcon) {
                toggleIcon.classList.remove('fa-bars');
                toggleIcon.classList.add('fa-chevron-right');
                toggleIcon.style.transform = 'rotate(0deg)';
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
                
                const sidebar = document.getElementById('sidebar');
                // 如果侧边栏是展开状态，执行切换
                // 如果是收缩状态，不执行切换（改为悬停显示）
                if (!sidebar || !sidebar.classList.contains('sidebar-collapsed')) {
                    toggleSubmenu(this);
                }
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
     * 初始化收缩状态下的悬停效果
     */
    function initCollapsedHover() {
        const sidebar = document.getElementById('sidebar');
        if (!sidebar) return;
        
        const submenuToggles = document.querySelectorAll('.sidebar-submenu-toggle');
        
        submenuToggles.forEach(toggle => {
            const parentLi = toggle.closest('.nav-item');
            const submenu = toggle.nextElementSibling;
            
            if (!submenu || !submenu.classList.contains('sidebar-submenu')) {
                return;
            }
            
            let hideTimeout;
            
            // 鼠标移入父菜单项
            parentLi.addEventListener('mouseenter', function() {
                if (!sidebar.classList.contains('sidebar-collapsed')) {
                    return;
                }
                
                clearTimeout(hideTimeout);
                showCollapsedSubmenu(this, submenu);
            });
            
            // 鼠标移出父菜单项
            parentLi.addEventListener('mouseleave', function() {
                if (!sidebar.classList.contains('sidebar-collapsed')) {
                    return;
                }
                
                hideTimeout = setTimeout(() => {
                    hideCollapsedSubmenu(submenu);
                }, 200);
            });
            
            // 鼠标移入浮动子菜单时保持显示
            submenu.addEventListener('mouseenter', function() {
                if (!sidebar.classList.contains('sidebar-collapsed')) {
                    return;
                }
                clearTimeout(hideTimeout);
            });
            
            // 鼠标移出浮动子菜单时隐藏
            submenu.addEventListener('mouseleave', function() {
                if (!sidebar.classList.contains('sidebar-collapsed')) {
                    return;
                }
                
                hideTimeout = setTimeout(() => {
                    hideCollapsedSubmenu(submenu);
                }, 200);
            });
        });
    }
    
    /**
     * 显示收缩状态下的子菜单
     */
    function showCollapsedSubmenu(parentItem, submenu) {
        // 隐藏其他悬浮菜单
        const allFloatingMenus = document.querySelectorAll('.sidebar-submenu-floating');
        allFloatingMenus.forEach(menu => {
            if (menu !== submenu) {
                menu.classList.remove('show');
            }
        });
        
        // 添加浮动菜单类
        submenu.classList.add('sidebar-submenu-floating');
        
        // 计算位置
        const rect = parentItem.getBoundingClientRect();
        const sidebar = document.getElementById('sidebar');
        const sidebarRect = sidebar.getBoundingClientRect();
        
        submenu.style.position = 'fixed';
        submenu.style.left = sidebarRect.right + 'px';
        submenu.style.top = rect.top + 'px';
        submenu.style.width = '200px';
        submenu.style.zIndex = '1000';
        
        // 显示菜单
        setTimeout(() => {
            submenu.classList.add('show');
        }, 10);
    }
    
    /**
     * 隐藏收缩状态下的子菜单
     */
    function hideCollapsedSubmenu(submenu) {
        submenu.classList.remove('show');
        setTimeout(() => {
            submenu.classList.remove('sidebar-submenu-floating');
            submenu.style.position = '';
            submenu.style.left = '';
            submenu.style.top = '';
            submenu.style.width = '';
            submenu.style.zIndex = '';
        }, 300);
    }
    
    /**
     * 恢复侧边栏状态（立即执行，避免闪烁）
     */
    function restoreState() {
        const isCollapsed = localStorage.getItem(STORAGE_KEY) === 'true';
        
        if (isCollapsed) {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.querySelector('.main-content');
            const toggleIcon = document.getElementById('toggle-icon-navbar');
            
            if (sidebar) {
                // 立即应用，不使用过渡动画
                sidebar.style.transition = 'none';
                sidebar.classList.add('sidebar-collapsed');
                // 强制重排后恢复过渡
                setTimeout(() => {
                    sidebar.style.transition = '';
                }, 0);
            }
            if (mainContent) {
                mainContent.style.transition = 'none';
                mainContent.classList.add('main-content-expanded');
                setTimeout(() => {
                    mainContent.style.transition = '';
                }, 0);
            }
            if (toggleIcon) {
                toggleIcon.classList.remove('fa-bars');
                toggleIcon.classList.add('fa-chevron-right');
                toggleIcon.style.transform = 'rotate(0deg)';
            }
        }
    }
    
    /**
     * 预加载状态（在DOM加载前执行，防止闪烁）
     */
    function preloadState() {
        const isCollapsed = localStorage.getItem(STORAGE_KEY) === 'true';
        
        if (isCollapsed) {
            // 添加样式标签立即应用收缩状态
            const style = document.createElement('style');
            style.id = 'sidebar-preload-style';
            style.textContent = `
                .sidebar { width: var(--sidebar-collapsed-width) !important; }
                .main-content { margin-left: var(--sidebar-collapsed-width) !important; }
                .sidebar .nav-link span { display: none !important; }
                .sidebar .submenu-icon { display: none !important; }
                .sidebar .sidebar-submenu { display: none !important; }
            `;
            document.head.appendChild(style);
            
            // DOM加载完成后移除预加载样式
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(() => {
                    const preloadStyle = document.getElementById('sidebar-preload-style');
                    if (preloadStyle) {
                        preloadStyle.remove();
                    }
                }, 100);
            });
        }
    }
    
    /**
     * 公开 API
     */
    return {
        init: init,
        toggle: toggleSidebar,
        preloadState: preloadState
    };
})();

// 立即执行预加载（在DOM加载前）
SidebarModern.preloadState();

// 页面加载时初始化
document.addEventListener('DOMContentLoaded', function() {
    SidebarModern.init();
});
