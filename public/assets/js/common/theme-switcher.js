/**
 * 主题切换器
 * 根据用户的主题偏好动态应用 CSS 变量
 */

const ThemeSwitcher = (function() {
    'use strict';

    const THEME_CONFIG = {
        blue: {
            primary: '#0066cc',
            primaryDark: '#004499',
            primaryLight: '#e6f2ff',
            cardHeaderBg: '#f0f7ff',
            navbarBg: 'linear-gradient(135deg, #ffffff 0%, #f8fafc 100%)',
            navbarBrand: '#0066cc',
        },
        purple: {
            primary: '#7c3aed',
            primaryDark: '#6d28d9',
            primaryLight: '#ede9fe',
            cardHeaderBg: '#f5f3ff',
            navbarBg: 'linear-gradient(135deg, #faf5ff 0%, #f3e8ff 100%)',
            navbarBrand: '#7c3aed',
        },
        green: {
            primary: '#10b981',
            primaryDark: '#059669',
            primaryLight: '#d1fae5',
            cardHeaderBg: '#f0fdf4',
            navbarBg: 'linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%)',
            navbarBrand: '#10b981',
        },
        orange: {
            primary: '#f59e0b',
            primaryDark: '#d97706',
            primaryLight: '#fef3c7',
            cardHeaderBg: '#fffbf0',
            navbarBg: 'linear-gradient(135deg, #fffbf0 0%, #fef3c7 100%)',
            navbarBrand: '#f59e0b',
        },
        pink: {
            primary: '#ec4899',
            primaryDark: '#db2777',
            primaryLight: '#fbcfe8',
            cardHeaderBg: '#fdf2f8',
            navbarBg: 'linear-gradient(135deg, #fdf2f8 0%, #fce7f3 100%)',
            navbarBrand: '#ec4899',
        },
        cyan: {
            primary: '#06b6d4',
            primaryDark: '#0891b2',
            primaryLight: '#cffafe',
            cardHeaderBg: '#ecfdf5',
            navbarBg: 'linear-gradient(135deg, #ecfdf5 0%, #cffafe 100%)',
            navbarBrand: '#06b6d4',
        },
    };

    /**
     * 将十六进制颜色转换为 RGB 格式
     */
    function hexToRgb(hex) {
        const result = /^#?([a-f\\d]{2})([a-f\\d]{2})([a-f\\d]{2})$/i.exec(hex);
        return result ? `${parseInt(result[1], 16)}, ${parseInt(result[2], 16)}, ${parseInt(result[3], 16)}` : '0, 102, 204';
    }

    /**
     * 初始化主题
     */
    function init() {
        loadTheme();
    }

    /**
     * 加载用户的主题偏好
     */
    function loadTheme() {
        // 从 localStorage 获取主题
        const savedTheme = localStorage.getItem('userTheme');
        
        if (savedTheme) {
            applyTheme(savedTheme);
        } else {
            // 从服务器获取用户的主题偏好
            fetchThemeFromServer();
        }
    }

    /**
     * 从服务器获取主题配置
     */
    function fetchThemeFromServer() {
        const themeConfigUrl = document.querySelector('meta[name="theme-config-url"]');
        
        if (!themeConfigUrl) {
            // 如果没有配置 URL，使用默认主题
            applyTheme('blue');
            return;
        }

        fetch(themeConfigUrl.getAttribute('content'))
            .then(response => response.json())
            .then(data => {
                const theme = data.theme || 'blue';
                applyTheme(theme);
                localStorage.setItem('userTheme', theme);
            })
            .catch(error => {
                console.error('Failed to load theme config:', error);
                applyTheme('blue');
            });
    }

    /**
     * 应用主题
     */
    function applyTheme(themeName) {
        const theme = THEME_CONFIG[themeName] || THEME_CONFIG.blue;

        // 应用 CSS 变量 - 这是最重要的，会影响所有使用 CSS 变量的元素
        const root = document.documentElement;
        root.style.setProperty('--primary-color', theme.primary);
        root.style.setProperty('--primary-color-rgb', hexToRgb(theme.primary));
        root.style.setProperty('--primary-dark', theme.primaryDark);
        root.style.setProperty('--primary-darker', theme.primaryDarker || theme.primaryDark);
        root.style.setProperty('--primary-light', theme.primaryLight);
        root.style.setProperty('--card-header-bg', theme.cardHeaderBg);
        
        // 应用辅助色 RGB 值
        root.style.setProperty('--success-color-rgb', hexToRgb('#00cc99'));
        root.style.setProperty('--warning-color-rgb', hexToRgb('#ffb800'));
        root.style.setProperty('--danger-color-rgb', hexToRgb('#ff6b6b'));
        root.style.setProperty('--info-color-rgb', hexToRgb('#0099ff'));

        // 应用导航栏样式
        const navbar = document.querySelector('.navbar');
        if (navbar) {
            navbar.style.background = theme.navbarBg;
        }

        const navbarBrand = document.querySelector('.navbar-brand');
        if (navbarBrand) {
            navbarBrand.style.color = theme.navbarBrand + ' !important';
        }

        // 应用卡片头部样式
        applyCardHeaderStyles(themeName, theme);

        // 保存到 localStorage
        localStorage.setItem('userTheme', themeName);
    }

    /**
     * 应用卡片头部样式
     */
    function applyCardHeaderStyles(themeName, theme) {
        const cardHeaders = document.querySelectorAll('.card-header');
        
        cardHeaders.forEach(header => {
            // 移除所有主题类
            header.classList.remove('theme-blue', 'theme-purple', 'theme-green', 'theme-orange', 'theme-pink', 'theme-cyan');
            
            // 添加当前主题类
            if (themeName !== 'blue') {
                header.classList.add('theme-' + themeName);
            }
        });
    }

    /**
     * 切换主题
     */
    function switchTheme(themeName) {
        if (THEME_CONFIG[themeName]) {
            applyTheme(themeName);
        }
    }

    /**
     * 获取当前主题
     */
    function getCurrentTheme() {
        return localStorage.getItem('userTheme') || 'blue';
    }

    /**
     * 公开 API
     */
    return {
        init: init,
        switchTheme: switchTheme,
        getCurrentTheme: getCurrentTheme,
        applyTheme: applyTheme,
    };
})();

// 立即初始化主题（不等待 DOMContentLoaded）
ThemeSwitcher.init();

// 页面加载时再次初始化主题（确保所有元素都已加载）
document.addEventListener('DOMContentLoaded', function() {
    ThemeSwitcher.init();
});
