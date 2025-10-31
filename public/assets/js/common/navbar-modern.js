/* ============================================
   现代化导航栏脚本
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
        
        initSearch();
        initNotifications();
        initUserMenu();
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
     */
    function initUserMenu() {
        const userMenu = document.querySelector('.navbar-user');
        if (!userMenu) return;
        
        // 用户菜单的下拉功能由 Bootstrap 处理
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
