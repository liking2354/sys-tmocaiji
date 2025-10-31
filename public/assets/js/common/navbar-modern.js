/* ============================================
   现代化导航栏脚本 - 完全隔离版本 v2
   
   设计原则：
   1. 只在导航栏内部处理事件
   2. 不监听 document 级别的事件
   3. 每个下拉菜单独立处理
   4. 完全隔离，不与其他页面事件冲突
   5. 不使用全局点击处理器
   ============================================ */

const NavbarModern = (function() {
    'use strict';
    
    let initialized = false;
    
    /**
     * 初始化导航栏
     */
    function init() {
        if (initialized) {
            return;
        }
        initialized = true;
        
        console.log('[NavbarModern] 初始化导航栏');
        
        initSearch();
        initNotifications();
        // 下拉菜单处理已移至 simple-dropdown.js
        // initUserMenu();
        // initAllDropdowns();
    }
    
    /**
     * 初始化搜索功能
     */
    function initSearch() {
        const searchInput = document.getElementById('navbar-search-input');
        if (!searchInput) return;
        
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                performSearch(this.value);
            }
        });
    }
    
    /**
     * 执行搜索
     */
    function performSearch(query) {
        if (!query.trim()) return;
        
        console.log('搜索:', query);
        // TODO: 实现搜索功能
    }
    
    /**
     * 初始化通知中心
     */
    function initNotifications() {
        const notificationBtn = document.querySelector('.navbar-notifications');
        if (!notificationBtn) return;
        
        notificationBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // TODO: 显示通知面板
            console.log('打开通知中心');
        });
    }
    
    /**
     * 初始化用户菜单
     * 注意：用户菜单处理已移至 dropdown-manager.js
     * 本函数已禁用以避免冲突
     */
    function initUserMenu() {
        console.log('[NavbarModern] 用户菜单处理已禁用，由 DropdownManager 处理');
    }
    
    /**
     * 初始化所有下拉菜单
     * 注意：下拉菜单处理已移至 dropdown-manager.js
     * 本函数已禁用以避免冲突
     */
    function initAllDropdowns() {
        console.log('[NavbarModern] 下拉菜单处理已禁用，由 DropdownManager 处理');
    }
    
    /**
     * 关闭其他所有下拉菜单
     * @param {Element} currentToggle - 当前下拉菜单切换按钮
     */
    function closeOtherDropdowns(currentToggle) {
        const navbar = document.getElementById('navbar');
        if (!navbar) return;
        
        const dropdownToggles = navbar.querySelectorAll('[data-toggle="dropdown"]');
        
        dropdownToggles.forEach(toggle => {
            // 跳过当前菜单
            if (toggle === currentToggle) {
                return;
            }
            
            // 获取下拉菜单容器
            const dropdownMenu = toggle.nextElementSibling;
            if (!dropdownMenu || !dropdownMenu.classList.contains('dropdown-menu')) {
                return;
            }
            
            // 关闭菜单
            dropdownMenu.classList.remove('show');
            toggle.setAttribute('aria-expanded', 'false');
        });
    }
    
    /**
     * 公开 API
     */
    return {
        init: init
    };
})();

// 页面加载时初始化
document.addEventListener('DOMContentLoaded', function() {
    NavbarModern.init();
});
